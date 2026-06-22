<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reminder sweep: an hourly cron picks up events crossing the 3-day / 24-hour marks.
// withoutOverlapping guards against a long run colliding with the next tick (double-send).
Schedule::command('events:send-reminders')->hourly()->withoutOverlapping();
