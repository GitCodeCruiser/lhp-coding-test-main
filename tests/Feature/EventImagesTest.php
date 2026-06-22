<?php

use App\Models\Event;
use App\Models\EventImage;
use Database\Seeders\EventImageSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EventImageSeeder', function () {
    beforeEach(function () {
        Event::factory()->count(8)->create();
        $this->seed(EventImageSeeder::class);
    });

    it('attaches 2 to 3 images to every event', function () {
        $events = Event::withCount('images')->get();

        expect($events)->toHaveCount(8);

        $events->each(function (Event $event) {
            expect($event->images_count)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(3);
        });
    });

    it('serves every image from a local file that exists on disk', function () {
        EventImage::all()->each(function (EventImage $image) {
            expect($image->path)->toStartWith('/event-images/');
            expect(file_exists(public_path(ltrim($image->path, '/'))))->toBeTrue();
        });
    });

    it('orders the images relation by sort_order', function () {
        $event = Event::has('images')->with('images')->first();
        $orders = $event->images->pluck('sort_order')->all();

        expect($orders)->toBe(collect($orders)->sort()->values()->all());
    });
});

describe('event_images table and relationships', function () {
    it('exposes the images relation and its belongs-to inverse', function () {
        $event = Event::factory()->create();

        $first = EventImage::create([
            'event_id' => $event->id,
            'path' => '/event-images/aurora-stage.svg',
            'alt' => 'Stage washed in aurora-green light',
            'sort_order' => 0,
            'is_primary' => true,
        ]);
        $second = EventImage::create([
            'event_id' => $event->id,
            'path' => '/event-images/city-neon.svg',
            'alt' => 'Neon-lit city skyline at night',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        $images = $event->fresh()->images;

        expect($images)->toHaveCount(2);
        expect($images[0]->id)->toBe($first->id);
        expect($images[1]->id)->toBe($second->id);
        expect($second->event->id)->toBe($event->id);
    });

    it('forbids a duplicate sort_order for the same event', function () {
        $event = Event::factory()->create();

        EventImage::create([
            'event_id' => $event->id,
            'path' => '/event-images/aurora-stage.svg',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        expect(fn () => EventImage::create([
            'event_id' => $event->id,
            'path' => '/event-images/city-neon.svg',
            'sort_order' => 0,
            'is_primary' => false,
        ]))->toThrow(QueryException::class);
    });

    it('cascade-deletes its images when the event is deleted', function () {
        $event = Event::factory()->create();

        EventImage::create([
            'event_id' => $event->id,
            'path' => '/event-images/aurora-stage.svg',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        expect(EventImage::count())->toBe(1);

        $event->delete();

        expect(EventImage::count())->toBe(0);
    });
});
