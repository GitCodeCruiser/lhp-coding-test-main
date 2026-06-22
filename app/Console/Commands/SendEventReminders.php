<?php

namespace App\Console\Commands;

use App\Enums\ReminderWindow;
use App\Mail\EventReminder;
use App\Models\Attendee;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

#[Signature('events:send-reminders')]
#[Description('Email attendees a reminder 3 days and 24 hours before their event starts.')]
class SendEventReminders extends Command
{
    public function handle(): int
    {
        $now = CarbonImmutable::now();

        // Threshold windows keyed off the event start (created_time, an indexed
        // integer the app already treats as the event's time). The reminder_*_sent_at
        // guard makes each reminder fire at most once, so the command is safe to run
        // hourly and to re-run, and it catches up if a scheduled run is missed.
        // 3-day window is 24h–72h out (so events <24h away get only the 24h reminder).
        $count = $this->dispatchWindow('reminder_3d_sent_at', $now->addDay(), $now->addDays(3), ReminderWindow::ThreeDay)
            + $this->dispatchWindow('reminder_24h_sent_at', $now, $now->addDay(), ReminderWindow::OneDay);

        $this->info("Queued {$count} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Queue the reminder for every attendee whose event starts within [$from, $to]
     * and who hasn't received this reminder yet, then mark it sent.
     */
    private function dispatchWindow(string $column, CarbonInterface $from, CarbonInterface $to, ReminderWindow $window): int
    {
        $count = 0;

        Attendee::query()
            ->whereNull($column)
            ->whereHas('event', fn (Builder $query) => $query->whereBetween('created_time', [$from->timestamp, $to->timestamp]))
            ->chunkById(500, function ($attendees) use ($column, $window, &$count): void {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(new EventReminder($attendee, $window));
                }

                // Safe to stamp the filtered column mid-iteration: chunkById cursors by
                // id, so already-marked rows are never revisited. Bulk-update the chunk.
                Attendee::whereKey($attendees->modelKeys())->update([$column => now()]);
                $count += $attendees->count();
            });

        return $count;
    }
}
