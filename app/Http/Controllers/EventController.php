<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendeeRequest;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use App\Models\EventImage;
use App\Support\Cities;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    /**
     * Half-width (degrees) of the location bounding box. The seeder jitters events
     * ±0.5° around each city anchor, so ±0.6° captures a city's whole cluster with a
     * small margin while minimising bleed into neighbouring cities.
     */
    private const LOCATION_BOX_DEGREES = 0.6;

    /** Max markers returned to the map — clustering handles density; full dataset is 1.25M. */
    private const MAP_MARKER_LIMIT = 1000;

    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
            ],
            'statuses' => self::STATUSES,
        ]);
    }

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', [
            'filters' => $this->currentFilters($request),
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'locations' => Cities::options(),
        ]);
    }

    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', [
            'filters' => $this->currentFilters($request),
            'statuses' => self::STATUSES,
            'types' => self::TYPES,
            'locations' => Cities::options(),
        ]);
    }

    /**
     * Single event feed for the visual pages. Returns paginated full events for the
     * list/card view, OR — when map viewport bounds (north/south/east/west) are
     * present — a capped set of lightweight markers for the map. Same envelope
     * either way: { data, total, current_page, last_page, stats }.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $request->filled(['north', 'south', 'east', 'west'])
                ? $this->mapPayload($request)
                : $this->listPayload($request),
        );
    }

    public function show(Event $event): Response
    {
        $event->load('user', 'images');
        $event->loadCount('attendees');
        $event->setAttribute('location_label', Cities::nearestLabel($event->latitude, $event->longitude));

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Register interest in an event. Idempotent: the unique (event_id, email)
     * index means a repeat registration is a no-op, and the confirmation email
     * only fires on the first registration via wasRecentlyCreated.
     */
    public function register(StoreAttendeeRequest $request, Event $event): RedirectResponse
    {
        if (in_array($event->status, ['cancelled', 'sold_out'], true)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Registration is closed for this event.']);
        }

        $attendee = $event->attendees()->firstOrCreate(
            ['email' => $request->validated('email')],
            [
                'name' => $request->validated('name'),
                'timezone' => $request->validated('timezone'),
            ],
        );

        if ($attendee->wasRecentlyCreated) {
            // Queued mailable: SerializesModels reloads the attendee (and lazy-loads
            // its event) when the job runs, so no need to pre-set the relation here.
            Mail::to($attendee->email)->queue(new EventRegistrationConfirmation($attendee));
        }

        return back()->with('toast', $attendee->wasRecentlyCreated
            ? ['type' => 'success', 'message' => "You're registered — a confirmation email is on its way."]
            : ['type' => 'info', 'message' => "You're already registered for this event."]);
    }

    /**
     * List/card mode: paginated full events with a human-readable location label.
     *
     * @return array<string, mixed>
     */
    private function listPayload(Request $request): array
    {
        $start = microtime(true);

        $events = $this->applyEventFilters(Event::with('user', 'images'), $request)
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $items = $events->getCollection()
            ->map(fn (Event $event): array => $this->listItemFor($event))
            ->all();

        return [
            'data' => $items,
            'total' => $events->total(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'stats' => $this->stats($start, $items),
        ];
    }

    /**
     * Shape a single card event: only the fields the grid/detail link needs, with a
     * server-computed location label. Keeps the heavy parts of the raw payload
     * (notes, tags, …) off the wire — the list ships ~50 of these per page.
     *
     * @return array<string, mixed>
     */
    private function listItemFor(Event $event): array
    {
        $payload = $event->payload;

        return [
            'id' => $event->id,
            'type' => $event->type,
            'status' => $event->status,
            'created_time' => $event->created_time,
            'latitude' => $event->latitude,
            'longitude' => $event->longitude,
            'location_label' => Cities::nearestLabel($event->latitude, $event->longitude),
            'user' => $event->user ? ['id' => $event->user->id, 'name' => $event->user->name] : null,
            'images' => $event->images->map(fn (EventImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'alt' => $image->alt,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
            ])->all(),
            'payload' => [
                'name' => $payload['name'] ?? null,
                'description' => $payload['description'] ?? null,
                'venue' => ['name' => $payload['venue']['name'] ?? null],
                'schedule' => [
                    'starts_at' => $payload['schedule']['starts_at'] ?? null,
                    'ends_at' => $payload['schedule']['ends_at'] ?? null,
                ],
                'pricing' => [
                    'currency' => $payload['pricing']['currency'] ?? null,
                    'min_price' => $payload['pricing']['min_price'] ?? null,
                ],
            ],
        ];
    }

    /**
     * Map mode: a capped set of lightweight markers constrained to the viewport
     * bounds (which are the source of truth for "which events" — so the location
     * filter is a camera command on the client, not applied here).
     *
     * @return array<string, mixed>
     */
    private function mapPayload(Request $request): array
    {
        $start = microtime(true);

        $query = $this->applyEventFilters(
            Event::query()->whereNotNull('latitude')->whereNotNull('longitude'),
            $request,
        )
            ->whereBetween('latitude', [(float) $request->input('south'), (float) $request->input('north')])
            ->whereBetween('longitude', [(float) $request->input('west'), (float) $request->input('east')]);

        $total = (clone $query)->count();

        $markers = $query
            ->with('primaryImage')
            ->orderByDesc('created_time')
            ->limit(self::MAP_MARKER_LIMIT)
            ->get()
            ->map(fn (Event $event): array => $this->markerFor($event))
            ->all();

        return [
            'data' => $markers,
            'total' => $total,
            'current_page' => 1,
            'last_page' => 1,
            'stats' => $this->stats($start, $markers),
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array{ms: int, bytes: int}
     */
    private function stats(float $start, array $items): array
    {
        return [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            // Payload-size telemetry. Re-encoding to measure costs an extra pass, so
            // only do it with debug on (it's a dev/demo metric, not needed in prod).
            'bytes' => config('app.debug') ? strlen((string) json_encode($items)) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function markerFor(Event $event): array
    {
        $payload = $event->payload;

        return [
            'id' => $event->id,
            'lat' => (float) $event->latitude,
            'lng' => (float) $event->longitude,
            'title' => $payload['name'] ?? ucfirst($event->type).' event',
            'type' => $event->type,
            'status' => $event->status,
            'location_label' => Cities::nearestLabel($event->latitude, $event->longitude),
            'starts_at' => (int) ($payload['schedule']['starts_at'] ?? $event->created_time),
            'price' => (float) ($payload['pricing']['min_price'] ?? 0),
            'image' => $event->primaryImage?->path,
        ];
    }

    /**
     * Apply the shared event filters (status, type, date range, location) to a query.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function applyEventFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($this->dayBoundary($request->input('from'), false), fn (Builder $q, int $from) => $q->where('created_time', '>=', $from))
            ->when($this->dayBoundary($request->input('to'), true), fn (Builder $q, int $to) => $q->where('created_time', '<=', $to))
            ->when($this->locationPreset($request), fn (Builder $q, array $preset) => $this->applyLocationFilter($q, $preset));
    }

    /**
     * @return array{status: string, type: string, location: string, from: string, to: string}
     */
    private function currentFilters(Request $request): array
    {
        return [
            'status' => (string) $request->input('status', ''),
            'type' => (string) $request->input('type', ''),
            'location' => (string) $request->input('location', ''),
            'from' => (string) $request->input('from', ''),
            'to' => (string) $request->input('to', ''),
        ];
    }

    /**
     * Convert a Y-m-d string to the UTC start/end-of-day unix timestamp used by
     * created_time. Returns null for an empty or unparseable date so the filter is
     * skipped rather than throwing (e.g. a hand-crafted ?from=abc query).
     */
    private function dayBoundary(?string $date, bool $endOfDay): ?int
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            $day = CarbonImmutable::parse($date, 'UTC');
        } catch (\Throwable) {
            return null;
        }

        return (int) ($endOfDay ? $day->endOfDay() : $day->startOfDay())->timestamp;
    }

    /**
     * @return array{label: string, lat: float, lng: float, region: string}|null
     */
    private function locationPreset(Request $request): ?array
    {
        return Cities::find((string) $request->input('location', ''));
    }

    /**
     * Restrict the query to a fixed-degree bounding box around the city's
     * coordinates. A fixed degree box (rather than a km radius) matches the
     * seeder's degree-based jitter and keeps neighbouring cities from bleeding in.
     *
     * @param  Builder<Event>  $query
     * @param  array{label: string, lat: float, lng: float, region: string}  $preset
     */
    private function applyLocationFilter(Builder $query, array $preset): void
    {
        $delta = self::LOCATION_BOX_DEGREES;

        $query
            ->whereBetween('latitude', [$preset['lat'] - $delta, $preset['lat'] + $delta])
            ->whereBetween('longitude', [$preset['lng'] - $delta, $preset['lng'] + $delta]);
    }
}
