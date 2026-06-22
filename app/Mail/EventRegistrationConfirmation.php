<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Support\Cities;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class EventRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee) {}

    public function envelope(): Envelope
    {
        $event = $this->attendee->event;
        $name = $event->payload['name'] ?? ucfirst($event->type).' event';

        return new Envelope(subject: "You're registered: {$name}");
    }

    public function content(): Content
    {
        $event = $this->attendee->event;
        // payload is cast to array on the Event model, so it's safe to index directly.
        $payload = $event->payload;

        // Show the start in the attendee's captured zone (validated to be a real
        // IANA zone), falling back to UTC when none was provided.
        $tz = $this->attendee->timezone ?: 'UTC';
        $startsAt = (int) ($payload['schedule']['starts_at'] ?? $event->created_time);

        return new Content(
            markdown: 'emails.registration-confirmation',
            with: [
                'attendeeName' => $this->attendee->name,
                'eventName' => $payload['name'] ?? ucfirst($event->type).' event',
                'venue' => $payload['venue']['name'] ?? null,
                'location' => Cities::nearestLabel($event->latitude, $event->longitude),
                'when' => Carbon::createFromTimestamp($startsAt, $tz)->format('D, M j, Y · g:i A T'),
                'url' => route('events.show', $event->id),
            ],
        );
    }
}
