<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventImageSeeder extends Seeder
{
    /**
     * Local placeholder files (served from public/event-images). Each row is a
     * [filename, alt] pair; the files exist on disk so images are served locally
     * and reused across every event (no per-event files are generated).
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const CATALOG = [
        ['aurora-stage.svg', 'Stage washed in aurora-green light'],
        ['city-neon.svg', 'Neon-lit city skyline at night'],
        ['desert-festival.svg', 'Open-air desert festival at golden hour'],
        ['harbor-night.svg', 'Harbour skyline reflected on the water at night'],
        ['midnight-jam.svg', 'Equalizer bars glowing at a midnight jam'],
        ['sunrise-session.svg', 'Sunrise session with warm gradient sky'],
    ];

    private const BASE_PATH = '/event-images/';

    /** Events read per keyset page. */
    private const READ_CHUNK = 5000;

    /** Rows per INSERT (kept well under Postgres' 65535 bind-param limit). */
    private const INSERT_CHUNK = 2000;

    /** Emit a progress line roughly every this many inserted rows. */
    private const PROGRESS_EVERY = 50000;

    private int $rowsInserted = 0;

    private int $lastReported = 0;

    public function run(): void
    {
        $catalogCount = count(self::CATALOG);
        $now = now()->toDateTimeString();
        $start = microtime(true);

        // Query log would balloon memory across millions of inserts; the bulk
        // inserts below don't need it.
        DB::connection()->disableQueryLog();

        // Re-runnable: start clean so re-seeding can't collide with the
        // (event_id, sort_order) unique index.
        DB::table('event_images')->truncate();

        $this->command->info('Attaching placeholder images to events...');

        $buffer = [];
        $events = 0;

        Event::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById(self::READ_CHUNK, function ($chunk) use ($catalogCount, $now, &$buffer, &$events): void {
                foreach ($chunk as $event) {
                    // Deterministic but varied: derive a stable starting image and
                    // count (2 or 3) from the event id. Stepping by 2 over 6 files
                    // guarantees no repeated image within a single event.
                    $seed = abs(crc32((string) $event->id));
                    $count = 2 + ($seed % 2);
                    $base = $seed % $catalogCount;

                    for ($i = 0; $i < $count; $i++) {
                        [$file, $alt] = self::CATALOG[($base + $i * 2) % $catalogCount];

                        $buffer[] = [
                            'event_id' => $event->id,
                            'path' => self::BASE_PATH.$file,
                            'alt' => $alt,
                            'sort_order' => $i,
                            'is_primary' => $i === 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $events++;

                    if (count($buffer) >= self::INSERT_CHUNK) {
                        $this->flush($buffer);
                        $buffer = [];
                    }
                }
            });

        $this->flush($buffer);

        $elapsed = round(microtime(true) - $start, 1);
        $this->command->info("Done. {$this->rowsInserted} image rows for {$events} events in {$elapsed}s.");
    }

    /**
     * Insert buffered rows and report progress.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function flush(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table('event_images')->insert($rows);
        $this->rowsInserted += count($rows);

        if ($this->rowsInserted - $this->lastReported >= self::PROGRESS_EVERY) {
            $this->command->getOutput()->writeln("  inserted {$this->rowsInserted} image rows...");
            $this->lastReported = $this->rowsInserted;
        }
    }
}
