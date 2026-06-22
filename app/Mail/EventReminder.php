<?php

namespace App\Mail;

use App\Enums\ReminderWindow;
use App\Models\Attendee;
use App\Support\Cities;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class EventReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee, public ReminderWindow $window) {}

    public function envelope(): Envelope
    {
        $event = $this->attendee->event;
        $name = $event->payload['name'] ?? ucfirst($event->type).' event';

        return new Envelope(subject: "Reminder: {$name} is in {$this->window->lead()}");
    }

    public function content(): Content
    {
        $event = $this->attendee->event;
        $payload = $event->payload;

        // Render the start in the attendee's captured zone, falling back to UTC.
        $tz = $this->attendee->timezone ?: 'UTC';
        $startsAt = (int) ($payload['schedule']['starts_at'] ?? $event->created_time);

        return new Content(
            markdown: 'emails.events.reminder',
            with: [
                'attendeeName' => $this->attendee->name,
                'eventName' => $payload['name'] ?? ucfirst($event->type).' event',
                'lead' => $this->window->lead(),
                'venue' => $payload['venue']['name'] ?? null,
                'location' => Cities::nearestLabel($event->latitude, $event->longitude),
                'when' => Carbon::createFromTimestamp($startsAt, $tz)->format('D, M j, Y · g:i A T'),
                'url' => route('events.show', $event->id),
            ],
        );
    }
}
