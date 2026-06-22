<?php

use App\Support\Cities;

/*
* Pure unit tests for App\Support\Cities — the catalogue is a compile-time
* constant, so no database or application boot is required. We test the actual
* logic (nearest-city math) and guard the hand-maintained catalogue's integrity;
* trivial array-access wrappers (find()) are covered via the HTTP filter tests.
    */

it('options exposes a value/label/region triple for every city', function () {
    $options = Cities::options();

    expect($options)->toHaveCount(count(Cities::ALL));

    foreach ($options as $option) {
        expect($option)->toHaveKeys(['value', 'label', 'region', 'lat', 'lng']);
        expect($option['value'])->toBeString();
        expect($option['label'])->toBeString();
        expect($option['region'])->toBeString();
        expect($option['lat'])->toBeFloat(); // the map relies on these for flyTo / centring
        expect($option['lng'])->toBeFloat();
    }
});

it('nearestLabel resolves exact and jittered coordinates to the right city', function () {
    expect(Cities::nearestLabel(35.6762, 139.6503))->toBe('Tokyo, Japan'); // exact Tokyo anchor
    expect(Cities::nearestLabel(40.7128 + 0.4, -74.0060 + 0.4))->toBe('New York, USA'); // within seeder jitter
});

it('nearestLabel picks the closest city, not just the first match', function () {
    // A point next to Lyon (45.764, 4.8357), far from Paris.
    expect(Cities::nearestLabel(45.77, 4.84))->toBe('Lyon, France');
});

it('nearestLabel returns unavailable for null or far-flung coordinates', function () {
    expect(Cities::nearestLabel(null, 139.6503))->toBe('Location unavailable');
    expect(Cities::nearestLabel(35.6762, null))->toBe('Location unavailable');
    expect(Cities::nearestLabel(null, null))->toBe('Location unavailable');
    expect(Cities::nearestLabel(-45.0, -140.0))->toBe('Location unavailable'); // South Pacific, >100km from any city
});

it('every catalogue entry has the required keys and valid coordinate ranges', function () {
    foreach (Cities::ALL as $slug => $city) {
        expect($city)->toHaveKeys(['label', 'lat', 'lng', 'region']);
        expect($city['lat'])->toBeGreaterThanOrEqual(-90)->toBeLessThanOrEqual(90);
        expect($city['lng'])->toBeGreaterThanOrEqual(-180)->toBeLessThanOrEqual(180);
        expect($slug)->toMatch('/^[a-z0-9]+(-[a-z0-9]+)*$/');
    }
});
