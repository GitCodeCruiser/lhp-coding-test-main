# Event Visuals — Coding Test

A Laravel + Inertia (Vue 3) + Tailwind app for browsing a large, seeded events dataset. It provides two
distinct browsing layouts — an **animated card grid** and an **interactive map** — plus event detail
pages, interest registration, and confirmation/reminder emails.

**Stack:** Laravel 13, Inertia v2 + Vue 3 (`<script setup lang="ts">`), Tailwind v4, PostgreSQL,
Leaflet (map), Pest (tests). Local image files, no external image hosting.

> This README is the single source of truth for running the project. If a step is needed to run or
> review something, it's listed here. See [`DECISIONS.md`](DECISIONS.md) for the reasoning behind the
> main technical choices.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL (recommended for the full dataset; the **test suite** uses in-memory SQLite automatically)

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` defaults to SQLite. For the real dataset, switch the database block to PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lhp_coding_test
DB_USERNAME=postgres
DB_PASSWORD=
```

Create the database first if needed (e.g. `createdb lhp_coding_test`).

Mail and the queue are already set for local use:

```dotenv
MAIL_MAILER=log          # emails are written to storage/logs/laravel.log
QUEUE_CONNECTION=database # emails are queued (see "Emails & reminders" below)
```

### 3. Migrate & seed

```bash
php artisan migrate
php artisan db:seed
```

`db:seed` runs, in order:

1. **`EventSeeder`** — bulk-inserts events (and supporting users).
2. **`EventImageSeeder`** — attaches **2–3 local placeholder images** to every event (`event_images`
   table; files live in `public/event-images/`).

**Dataset size:** `EventSeeder` defaults to **1,250,000** events (≈2.5 GB), controlled by the `SEED_ROWS`
env var. Images **reuse the same 6 placeholder files** for every event (per the brief) — no extra image
files are generated regardless of dataset size.

**For development, seed a small set** (recommended for a quick review):

```bash
SEED_ROWS=5000 php artisan db:seed                 # bash / macOS / Linux
$env:SEED_ROWS=5000; php artisan db:seed           # Windows PowerShell
```

<details>
<summary>Seeding the full 1.25M dataset (all at once, or in blocks)</summary>

```bash
# All at once
SEED_ROWS=1250000 php artisan migrate:fresh --seed

# In blocks — EventSeeder appends on each run, so build up the dataset to keep
# per-run memory/time low, then attach images once at the end:
php artisan migrate:fresh
for i in 1 2 3 4 5; do SEED_ROWS=250000 php artisan db:seed --class=EventSeeder; done
php artisan db:seed --class=EventImageSeeder
```

`EventImageSeeder` truncates and re-attaches on every run, so it's safe to re-run after adding more
events — it always ends with 2–3 images per event.

</details>

### 4. Run the app

In two terminals:

```bash
php artisan serve     # backend  -> http://127.0.0.1:8000
npm run dev           # Vite dev server (hot reload)
```

## What to look at

| Page | URL | What it is |
| --- | --- | --- |
| **Event Visuals 1** | `/events-visual-1` | Animated **card grid** — filters, image carousel, infinite scroll |
| **Event Visuals 2** | `/events-visual-2` | Interactive **Leaflet map** — viewport loading, clustering, filters |
| **Event detail** | `/events/{id}` | Image gallery, human-readable location, local time, **registration form** |
| Listing (table) | `/events` | Simple paginated table (also the `/` redirect target) |

Both visual pages share one filter bar (**date range + location**, plus type/status) and the same
`/events/data` endpoint.

## Emails & reminders

Emails are **queued mailables**, so they don't send synchronously. With `MAIL_MAILER=log`, a "sent" email
is written to `storage/logs/laravel.log`.

**To actually send/log queued emails, do one of:**

```bash
php artisan queue:work          # run a worker (recommended), OR
# set QUEUE_CONNECTION=sync in .env to process inline (no worker needed)
```

- **Confirmation email** — sent when someone registers interest on an event detail page (first time only;
  re-registering is idempotent and sends nothing).
- **Reminder emails** — a `3 days before` and a `24 hours before` reminder per attendee. The command:

  ```bash
  php artisan events:send-reminders     # run manually, or let the scheduler run it hourly
  ```

  It's scheduled **hourly** (`routes/console.php`). To exercise the schedule locally, run
  `php artisan schedule:work`; in production, the standard cron entry
  `* * * * * php artisan schedule:run` drives it. The command is idempotent (guarded by
  `reminder_3d_sent_at` / `reminder_24h_sent_at`), so it's safe to run repeatedly.

**Quick end-to-end check:** set `QUEUE_CONNECTION=sync`, register on an event, then tail
`storage/logs/laravel.log` to see the confirmation; run `php artisan events:send-reminders` to see
reminders for events ~3 days / ~24 hours out.

## Testing & quality

```bash
./vendor/bin/pest            # full test suite (runs on in-memory SQLite, no setup needed)
./vendor/bin/pint            # PHP code style
./vendor/bin/phpstan analyse # static analysis (level 7)
npm run lint                 # ESLint
npx vue-tsc --noEmit         # TypeScript type-check
```

## Notes

- **Images** are real rows in the `event_images` table (not computed on the fly), seeded with local
  placeholder SVGs in `public/event-images/`. Each event gets 2–3 images, the first marked `is_primary`.
  Selection is deterministic (derived from the event id), so it stays varied but stable across re-seeds.
- **Locations** are derived from each event's latitude/longitude by snapping to the nearest of a local
  75-city catalogue (no external geocoding API).
- **Times** are stored as UTC and shown in the viewer's timezone on screen, and in the attendee's
  captured timezone in emails.
- See [`DECISIONS.md`](DECISIONS.md) for the full reasoning behind these and the other major choices.
