<?php

use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('registers an attendee and queues a confirmation email', function () {
    Mail::fake();
    $event = Event::factory()->create(['status' => 'published']);

    $this->from(route('events.show', $event))
        ->post(route('events.register', $event), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'timezone' => 'Europe/London',
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'success');

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'timezone' => 'Europe/London',
    ]);

    Mail::assertQueued(
        EventRegistrationConfirmation::class,
        fn ($mail) => $mail->hasTo('ada@example.com') && $mail->attendee->event_id === $event->id,
    );
});

it('is idempotent: a repeat registration adds no row and sends no second email', function () {
    Mail::fake();
    $event = Event::factory()->create(['status' => 'published']);
    $payload = ['name' => 'Ada', 'email' => 'ada@example.com', 'timezone' => 'UTC'];

    $this->post(route('events.register', $event), $payload);
    $this->from(route('events.show', $event))
        ->post(route('events.register', $event), $payload)
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'info');

    $this->assertDatabaseCount('attendees', 1);
    Mail::assertQueued(EventRegistrationConfirmation::class, 1);
});

it('validates name, email, and timezone', function () {
    Mail::fake();
    $event = Event::factory()->create(['status' => 'published']);

    $this->from(route('events.show', $event))
        ->post(route('events.register', $event), [
            'name' => '',
            'email' => 'not-an-email',
            'timezone' => 'Mars/Olympus',
        ])
        ->assertSessionHasErrors(['name', 'email', 'timezone']);

    $this->assertDatabaseCount('attendees', 0);
    Mail::assertNothingQueued();
});

it('rejects registration for a closed event', function (string $status) {
    Mail::fake();
    $event = Event::factory()->create(['status' => $status]);

    $this->from(route('events.show', $event))
        ->post(route('events.register', $event), [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'timezone' => 'UTC',
        ])
        ->assertSessionHas('toast', fn ($toast) => $toast['type'] === 'error');

    $this->assertDatabaseCount('attendees', 0);
    Mail::assertNothingQueued();
})->with(['cancelled', 'sold_out']);
