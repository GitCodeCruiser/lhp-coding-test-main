<?php

use App\Enums\ReminderWindow;
use App\Mail\EventReminder;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** An attendee on an event that starts $seconds from the (frozen) now. */
function attendeeStartingIn(int $seconds): Attendee
{
    return Attendee::factory()
        ->for(Event::factory()->create(['created_time' => now()->addSeconds($seconds)->timestamp]))
        ->create(['timezone' => 'UTC']);
}

it('queues a 3-day reminder for an event about three days out and marks it sent', function () {
    Mail::fake();
    $this->freezeTime();
    $attendee = attendeeStartingIn(2 * 86400); // 2 days → inside the 24h–72h window

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(
        EventReminder::class,
        fn ($mail) => $mail->window === ReminderWindow::ThreeDay && $mail->attendee->is($attendee),
    );
    expect($attendee->refresh()->reminder_3d_sent_at)->not->toBeNull();
    expect($attendee->reminder_24h_sent_at)->toBeNull();
});

it('queues a 24-hour reminder for an event within a day and marks it sent', function () {
    Mail::fake();
    $this->freezeTime();
    $attendee = attendeeStartingIn(12 * 3600); // 12 hours → inside the 0–24h window

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, fn ($mail) => $mail->window === ReminderWindow::OneDay);
    expect($attendee->refresh()->reminder_24h_sent_at)->not->toBeNull();
    expect($attendee->reminder_3d_sent_at)->toBeNull();
});

it('does not remind for events far in the future or already past', function () {
    Mail::fake();
    $this->freezeTime();
    attendeeStartingIn(10 * 86400);  // 10 days out
    attendeeStartingIn(-86400);      // started yesterday

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('is idempotent: a second run queues nothing new', function () {
    Mail::fake();
    $this->freezeTime();
    attendeeStartingIn(2 * 86400);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminder::class, 1);
});
