<?php

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->where('filters.from', '2023-01-01')
        );
});

it('returns a json page of events with load stats for lazy loading', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.created_time', 1_700_000_000)
        ->assertJsonPath('data.0.latitude', 40.7128)
        ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

it('shows an event detail page with its payload', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 1.5, 'lng' => 2.5]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.payload.name', 'Global Tech Summit')
        );
});

it('renders the two visualization pages and the dashboard without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
    $this->get(route('dashboard'))->assertOk();
});

it('renders the events-visual-1 page with the list of cities', function () {
    $this->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->has('locations')
        );
});

it('visual-1 page passes statuses, types, and filter defaults as props', function () {
    $this->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->has('statuses', 4)
            ->has('types', 8)
            ->where('filters.status', '')
            ->where('filters.type', '')
            ->where('filters.location', '')
            ->where('filters.from', '')
            ->where('filters.to', '')
        );
});

it('visual-1 page echoes active query filters back as props', function () {
    $this->get(route('events.visual1', ['status' => 'published', 'type' => 'concert', 'location' => 'london']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->where('filters.status', 'published')
            ->where('filters.type', 'concert')
            ->where('filters.location', 'london')
        );
});

it('visual-1 locations options contain value, label, and region keys', function () {
    $this->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->where('locations.0.value', fn ($v) => is_string($v) && $v !== '')
            ->where('locations.0.label', fn ($v) => is_string($v) && $v !== '')
            ->where('locations.0.region', fn ($v) => is_string($v) && $v !== '')
        );
});

it('filters the data endpoint by type', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['type' => 'concert', 'status' => 'published']);
    Event::factory()->for($user)->create(['type' => 'workshop', 'status' => 'published']);

    $this->getJson(route('events.data', ['type' => 'concert']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert');
});

it('filters the data endpoint by from date', function () {
    $user = User::factory()->create();

    // 2024-06-01 00:00:00 UTC = 1717200000
    Event::factory()->for($user)->create(['created_time' => 1_717_200_000]); // exactly on boundary
    Event::factory()->for($user)->create(['created_time' => 1_717_200_000 - 1]); // one second before

    $this->getJson(route('events.data', ['from' => '2024-06-01']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('filters the data endpoint by to date', function () {
    $user = User::factory()->create();

    // 2024-06-30 23:59:59 UTC = 1719791999
    Event::factory()->for($user)->create(['created_time' => 1_719_791_999]); // exactly on boundary
    Event::factory()->for($user)->create(['created_time' => 1_719_791_999 + 1]); // one second after

    $this->getJson(route('events.data', ['to' => '2024-06-30']))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('filters the data endpoint by date range combining from and to', function () {
    $user = User::factory()->create();

    Event::factory()->for($user)->create(['created_time' => 1_700_000_000]); // inside range
    Event::factory()->for($user)->create(['created_time' => 1_600_000_000]); // before range
    Event::factory()->for($user)->create(['created_time' => 1_800_000_000]); // after range

    $this->getJson(route('events.data', ['from' => '2023-01-01', 'to' => '2024-01-01']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.created_time', 1_700_000_000);
});

it('filters the data endpoint by location bounding box', function () {
    $user = User::factory()->create();

    // New York anchor: lat=40.7128, lng=-74.0060, box delta=0.6
    Event::factory()->for($user)->create([
        'latitude' => 40.7128,  // exactly on anchor — inside box
        'longitude' => -74.0060,
    ]);
    Event::factory()->for($user)->create([
        'latitude' => 10.0,     // far south — outside box
        'longitude' => -74.0060,
    ]);

    $this->getJson(route('events.data', ['location' => 'new-york']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.latitude', 40.7128);
});

it('ignores an unknown location slug and returns all events', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->count(3)->create();

    $this->getJson(route('events.data', ['location' => 'atlantis']))
        ->assertOk()
        ->assertJsonPath('total', 3);
});

it('attaches a location_label to each event in the data response', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $response = $this->getJson(route('events.data'))->assertOk();

    $this->assertArrayHasKey('location_label', $response->json('data.0'));
    $this->assertSame('New York, USA', $response->json('data.0.location_label'));
});

it('attaches location_label unavailable for events without coordinates', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'latitude' => null,
        'longitude' => null,
    ]);

    $response = $this->getJson(route('events.data'))->assertOk();

    $this->assertSame('Location unavailable', $response->json('data.0.location_label'));
});

it('attaches a location_label to the event show page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'latitude' => 48.8566,
        'longitude' => 2.3522,
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.location_label', 'Paris, France')
        );
});

it('data endpoint returns stats with ms and bytes keys', function () {
    config(['app.debug' => true]); // bytes telemetry is only measured with debug on

    $user = User::factory()->create();
    Event::factory()->for($user)->create();

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure(['stats' => ['ms', 'bytes']])
        ->assertJsonPath('stats.bytes', fn ($v) => $v > 0);
});

it('ignores an unparseable date filter instead of erroring', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->count(2)->create();

    $this->getJson(route('events.data', ['from' => 'not-a-date', 'to' => 'also-bad']))
        ->assertOk()
        ->assertJsonPath('total', 2);
});

it('excludes events outside the location box on the longitude axis', function () {
    $user = User::factory()->create();

    // New York anchor: lat=40.7128, lng=-74.0060, box delta=0.6
    Event::factory()->for($user)->create(['latitude' => 40.7128, 'longitude' => -74.0060]); // inside
    Event::factory()->for($user)->create(['latitude' => 40.7128, 'longitude' => 10.0]);     // right lat, wrong lng

    $this->getJson(route('events.data', ['location' => 'new-york']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.longitude', -74.0060);
});

it('labels coordinates far from any known city as unavailable', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 0.0, 'longitude' => 0.0]); // mid-ocean, >100km from any city

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonPath('data.0.location_label', 'Location unavailable');
});

it('with only a from date, shows events on or after it and hides earlier ones', function () {
    $user = User::factory()->create();
    $ts = fn (string $date) => CarbonImmutable::parse($date, 'UTC')->timestamp;

    Event::factory()->for($user)->create(['created_time' => $ts('2023-03-01 10:00:00')]); // before -> hidden
    Event::factory()->for($user)->create(['created_time' => $ts('2024-09-01 10:00:00')]); // after  -> shown
    Event::factory()->for($user)->create(['created_time' => $ts('2025-02-01 10:00:00')]); // after  -> shown

    $response = $this->getJson(route('events.data', ['from' => '2024-01-01']))->assertOk();
    $times = collect($response->json('data'))->pluck('created_time')->all();

    expect($response->json('total'))->toBe(2);
    expect($times)->toContain($ts('2024-09-01 10:00:00'), $ts('2025-02-01 10:00:00'));
    expect($times)->not->toContain($ts('2023-03-01 10:00:00'));
});

it('with only a to date, shows events on or before it and hides later ones', function () {
    $user = User::factory()->create();
    $ts = fn (string $date) => CarbonImmutable::parse($date, 'UTC')->timestamp;

    Event::factory()->for($user)->create(['created_time' => $ts('2023-03-01 10:00:00')]); // before -> shown
    Event::factory()->for($user)->create(['created_time' => $ts('2024-09-01 10:00:00')]); // before -> shown
    Event::factory()->for($user)->create(['created_time' => $ts('2025-02-01 10:00:00')]); // after  -> hidden

    $response = $this->getJson(route('events.data', ['to' => '2024-12-31']))->assertOk();
    $times = collect($response->json('data'))->pluck('created_time')->all();

    expect($response->json('total'))->toBe(2);
    expect($times)->toContain($ts('2023-03-01 10:00:00'), $ts('2024-09-01 10:00:00'));
    expect($times)->not->toContain($ts('2025-02-01 10:00:00'));
});

it('with both from and to, shows only events within the range', function () {
    $user = User::factory()->create();
    $ts = fn (string $date) => CarbonImmutable::parse($date, 'UTC')->timestamp;

    Event::factory()->for($user)->create(['created_time' => $ts('2022-12-31 10:00:00')]); // before range -> hidden
    Event::factory()->for($user)->create(['created_time' => $ts('2023-07-01 10:00:00')]); // in range     -> shown
    Event::factory()->for($user)->create(['created_time' => $ts('2024-06-15 10:00:00')]); // in range     -> shown
    Event::factory()->for($user)->create(['created_time' => $ts('2025-01-01 10:00:00')]); // after range  -> hidden

    $response = $this->getJson(route('events.data', ['from' => '2023-01-01', 'to' => '2024-12-31']))->assertOk();
    $times = collect($response->json('data'))->pluck('created_time')->all();

    expect($response->json('total'))->toBe(2);
    expect($times)->toContain($ts('2023-07-01 10:00:00'), $ts('2024-06-15 10:00:00'));
    expect($times)->not->toContain($ts('2022-12-31 10:00:00'));
    expect($times)->not->toContain($ts('2025-01-01 10:00:00'));
});

it('renders the map page with statuses, types, and location options', function () {
    $this->get(route('events.visual2'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualTwo')
            ->has('statuses', 4)
            ->has('types', 8)
            ->has('locations')
        );
});

// Map mode = the data endpoint with viewport bounds (north/south/east/west).
it('data endpoint in map mode returns lightweight markers', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 35.6762, 'longitude' => 139.6503]);

    $this->getJson(route('events.data', ['north' => 85, 'south' => -85, 'east' => 180, 'west' => -180]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'lat', 'lng', 'title', 'type', 'status', 'location_label', 'starts_at', 'price', 'image']],
            'total',
            'current_page',
            'last_page',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.location_label', 'Tokyo, Japan');
});

it('data endpoint in map mode excludes events without coordinates', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 40.7128, 'longitude' => -74.0060]);
    Event::factory()->for($user)->create(['latitude' => null, 'longitude' => null]);

    $this->getJson(route('events.data', ['north' => 85, 'south' => -85, 'east' => 180, 'west' => -180]))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

it('data endpoint in map mode constrains markers to the viewport bounding box', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['latitude' => 35.6762, 'longitude' => 139.6503]);  // Tokyo — inside bounds
    Event::factory()->for($user)->create(['latitude' => 51.5074, 'longitude' => -0.1278]);   // London — outside bounds

    // A box roughly around Japan.
    $this->getJson(route('events.data', ['north' => 38, 'south' => 33, 'east' => 142, 'west' => 137]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.location_label', 'Tokyo, Japan');
});
