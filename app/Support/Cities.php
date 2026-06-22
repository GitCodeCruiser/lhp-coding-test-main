<?php

namespace App\Support;

class Cities
{
    /**
     * Canonical city catalogue. Coordinates mirror the EventSeeder anchors, so the
     * location filter (bounding box) and the nearest-city label both align with the
     * seeded data. Keyed by slug, which is the value used by the location filter.
     *
     * @var array<string, array{label: string, lat: float, lng: float, region: string}>
     */
    public const ALL = [
        // United States
        'new-york' => ['label' => 'New York, USA', 'lat' => 40.7128, 'lng' => -74.0060, 'region' => 'United States'],
        'los-angeles' => ['label' => 'Los Angeles, USA', 'lat' => 34.0522, 'lng' => -118.2437, 'region' => 'United States'],
        'chicago' => ['label' => 'Chicago, USA', 'lat' => 41.8781, 'lng' => -87.6298, 'region' => 'United States'],
        'houston' => ['label' => 'Houston, USA', 'lat' => 29.7604, 'lng' => -95.3698, 'region' => 'United States'],
        'phoenix' => ['label' => 'Phoenix, USA', 'lat' => 33.4484, 'lng' => -112.0740, 'region' => 'United States'],
        'philadelphia' => ['label' => 'Philadelphia, USA', 'lat' => 39.9526, 'lng' => -75.1652, 'region' => 'United States'],
        'san-antonio' => ['label' => 'San Antonio, USA', 'lat' => 29.4241, 'lng' => -98.4936, 'region' => 'United States'],
        'san-diego' => ['label' => 'San Diego, USA', 'lat' => 32.7157, 'lng' => -117.1611, 'region' => 'United States'],
        'dallas' => ['label' => 'Dallas, USA', 'lat' => 32.7767, 'lng' => -96.7970, 'region' => 'United States'],
        'san-jose' => ['label' => 'San Jose, USA', 'lat' => 37.3382, 'lng' => -121.8863, 'region' => 'United States'],
        'austin' => ['label' => 'Austin, USA', 'lat' => 30.2672, 'lng' => -97.7431, 'region' => 'United States'],
        'san-francisco' => ['label' => 'San Francisco, USA', 'lat' => 37.7749, 'lng' => -122.4194, 'region' => 'United States'],
        'seattle' => ['label' => 'Seattle, USA', 'lat' => 47.6062, 'lng' => -122.3321, 'region' => 'United States'],
        'denver' => ['label' => 'Denver, USA', 'lat' => 39.7392, 'lng' => -104.9903, 'region' => 'United States'],
        'boston' => ['label' => 'Boston, USA', 'lat' => 42.3601, 'lng' => -71.0589, 'region' => 'United States'],
        'las-vegas' => ['label' => 'Las Vegas, USA', 'lat' => 36.1699, 'lng' => -115.1398, 'region' => 'United States'],
        'miami' => ['label' => 'Miami, USA', 'lat' => 25.7617, 'lng' => -80.1918, 'region' => 'United States'],
        'atlanta' => ['label' => 'Atlanta, USA', 'lat' => 33.7490, 'lng' => -84.3880, 'region' => 'United States'],
        'washington-dc' => ['label' => 'Washington, D.C., USA', 'lat' => 38.9072, 'lng' => -77.0369, 'region' => 'United States'],
        'nashville' => ['label' => 'Nashville, USA', 'lat' => 36.1627, 'lng' => -86.7816, 'region' => 'United States'],
        'portland' => ['label' => 'Portland, USA', 'lat' => 45.5152, 'lng' => -122.6784, 'region' => 'United States'],
        'new-orleans' => ['label' => 'New Orleans, USA', 'lat' => 29.9511, 'lng' => -90.0715, 'region' => 'United States'],

        // Canada
        'toronto' => ['label' => 'Toronto, Canada', 'lat' => 43.6532, 'lng' => -79.3832, 'region' => 'Canada'],
        'montreal' => ['label' => 'Montreal, Canada', 'lat' => 45.5019, 'lng' => -73.5674, 'region' => 'Canada'],
        'vancouver' => ['label' => 'Vancouver, Canada', 'lat' => 49.2827, 'lng' => -123.1207, 'region' => 'Canada'],
        'calgary' => ['label' => 'Calgary, Canada', 'lat' => 51.0447, 'lng' => -114.0719, 'region' => 'Canada'],
        'ottawa' => ['label' => 'Ottawa, Canada', 'lat' => 45.4215, 'lng' => -75.6972, 'region' => 'Canada'],
        'edmonton' => ['label' => 'Edmonton, Canada', 'lat' => 53.5461, 'lng' => -113.4938, 'region' => 'Canada'],
        'quebec-city' => ['label' => 'Quebec City, Canada', 'lat' => 46.8139, 'lng' => -71.2080, 'region' => 'Canada'],
        'winnipeg' => ['label' => 'Winnipeg, Canada', 'lat' => 49.8951, 'lng' => -97.1384, 'region' => 'Canada'],

        // Mexico
        'mexico-city' => ['label' => 'Mexico City, Mexico', 'lat' => 19.4326, 'lng' => -99.1332, 'region' => 'Mexico'],
        'guadalajara' => ['label' => 'Guadalajara, Mexico', 'lat' => 20.6597, 'lng' => -103.3496, 'region' => 'Mexico'],
        'monterrey' => ['label' => 'Monterrey, Mexico', 'lat' => 25.6866, 'lng' => -100.3161, 'region' => 'Mexico'],
        'puebla' => ['label' => 'Puebla, Mexico', 'lat' => 19.0414, 'lng' => -98.2063, 'region' => 'Mexico'],
        'tijuana' => ['label' => 'Tijuana, Mexico', 'lat' => 32.5149, 'lng' => -117.0382, 'region' => 'Mexico'],
        'cancun' => ['label' => 'Cancún, Mexico', 'lat' => 21.1619, 'lng' => -86.8515, 'region' => 'Mexico'],
        'merida' => ['label' => 'Mérida, Mexico', 'lat' => 20.9674, 'lng' => -89.5926, 'region' => 'Mexico'],

        // Europe
        'london' => ['label' => 'London, UK', 'lat' => 51.5074, 'lng' => -0.1278, 'region' => 'Europe'],
        'paris' => ['label' => 'Paris, France', 'lat' => 48.8566, 'lng' => 2.3522, 'region' => 'Europe'],
        'berlin' => ['label' => 'Berlin, Germany', 'lat' => 52.5200, 'lng' => 13.4050, 'region' => 'Europe'],
        'madrid' => ['label' => 'Madrid, Spain', 'lat' => 40.4168, 'lng' => -3.7038, 'region' => 'Europe'],
        'rome' => ['label' => 'Rome, Italy', 'lat' => 41.9028, 'lng' => 12.4964, 'region' => 'Europe'],
        'amsterdam' => ['label' => 'Amsterdam, Netherlands', 'lat' => 52.3676, 'lng' => 4.9041, 'region' => 'Europe'],
        'barcelona' => ['label' => 'Barcelona, Spain', 'lat' => 41.3851, 'lng' => 2.1734, 'region' => 'Europe'],
        'munich' => ['label' => 'Munich, Germany', 'lat' => 48.1351, 'lng' => 11.5820, 'region' => 'Europe'],
        'milan' => ['label' => 'Milan, Italy', 'lat' => 45.4642, 'lng' => 9.1900, 'region' => 'Europe'],
        'vienna' => ['label' => 'Vienna, Austria', 'lat' => 48.2082, 'lng' => 16.3738, 'region' => 'Europe'],
        'prague' => ['label' => 'Prague, Czechia', 'lat' => 50.0755, 'lng' => 14.4378, 'region' => 'Europe'],
        'lisbon' => ['label' => 'Lisbon, Portugal', 'lat' => 38.7223, 'lng' => -9.1393, 'region' => 'Europe'],
        'dublin' => ['label' => 'Dublin, Ireland', 'lat' => 53.3498, 'lng' => -6.2603, 'region' => 'Europe'],
        'copenhagen' => ['label' => 'Copenhagen, Denmark', 'lat' => 55.6761, 'lng' => 12.5683, 'region' => 'Europe'],
        'stockholm' => ['label' => 'Stockholm, Sweden', 'lat' => 59.3293, 'lng' => 18.0686, 'region' => 'Europe'],
        'oslo' => ['label' => 'Oslo, Norway', 'lat' => 59.9139, 'lng' => 10.7522, 'region' => 'Europe'],
        'helsinki' => ['label' => 'Helsinki, Finland', 'lat' => 60.1699, 'lng' => 24.9384, 'region' => 'Europe'],
        'brussels' => ['label' => 'Brussels, Belgium', 'lat' => 50.8503, 'lng' => 4.3517, 'region' => 'Europe'],
        'zurich' => ['label' => 'Zurich, Switzerland', 'lat' => 47.3769, 'lng' => 8.5417, 'region' => 'Europe'],
        'warsaw' => ['label' => 'Warsaw, Poland', 'lat' => 52.2297, 'lng' => 21.0122, 'region' => 'Europe'],
        'budapest' => ['label' => 'Budapest, Hungary', 'lat' => 47.4979, 'lng' => 19.0402, 'region' => 'Europe'],
        'athens' => ['label' => 'Athens, Greece', 'lat' => 37.9838, 'lng' => 23.7275, 'region' => 'Europe'],
        'lyon' => ['label' => 'Lyon, France', 'lat' => 45.7640, 'lng' => 4.8357, 'region' => 'Europe'],
        'hamburg' => ['label' => 'Hamburg, Germany', 'lat' => 53.5511, 'lng' => 9.9937, 'region' => 'Europe'],
        'manchester' => ['label' => 'Manchester, UK', 'lat' => 53.4808, 'lng' => -2.2426, 'region' => 'Europe'],
        'edinburgh' => ['label' => 'Edinburgh, UK', 'lat' => 55.9533, 'lng' => -3.1883, 'region' => 'Europe'],
        'frankfurt' => ['label' => 'Frankfurt, Germany', 'lat' => 50.1109, 'lng' => 8.6821, 'region' => 'Europe'],
        'krakow' => ['label' => 'Kraków, Poland', 'lat' => 50.0647, 'lng' => 19.9450, 'region' => 'Europe'],
        'porto' => ['label' => 'Porto, Portugal', 'lat' => 41.1579, 'lng' => -8.6291, 'region' => 'Europe'],
        'naples' => ['label' => 'Naples, Italy', 'lat' => 40.8518, 'lng' => 14.2681, 'region' => 'Europe'],

        // Asia & Middle East
        'tokyo' => ['label' => 'Tokyo, Japan', 'lat' => 35.6762, 'lng' => 139.6503, 'region' => 'Asia & Middle East'],
        'seoul' => ['label' => 'Seoul, South Korea', 'lat' => 37.5665, 'lng' => 126.9780, 'region' => 'Asia & Middle East'],
        'singapore' => ['label' => 'Singapore', 'lat' => 1.3521, 'lng' => 103.8198, 'region' => 'Asia & Middle East'],
        'dubai' => ['label' => 'Dubai, UAE', 'lat' => 25.2048, 'lng' => 55.2708, 'region' => 'Asia & Middle East'],

        // Oceania
        'sydney' => ['label' => 'Sydney, Australia', 'lat' => -33.8688, 'lng' => 151.2093, 'region' => 'Oceania'],
        'melbourne' => ['label' => 'Melbourne, Australia', 'lat' => -37.8136, 'lng' => 144.9631, 'region' => 'Oceania'],

        // South America
        'sao-paulo' => ['label' => 'São Paulo, Brazil', 'lat' => -23.5505, 'lng' => -46.6333, 'region' => 'South America'],
        'buenos-aires' => ['label' => 'Buenos Aires, Argentina', 'lat' => -34.6037, 'lng' => -58.3816, 'region' => 'South America'],
    ];

    /**
     * Max distance (km) from a known city before a location is treated as unknown.
     * The seeder jitters events up to ~78 km (±0.5° on both axes) from an anchor,
     * so 100 km labels every seeded event while still rejecting far-flung coords.
     */
    private const NEAREST_CITY_MAX_KM = 100;

    /**
     * Dropdown options, sorted by label. Coordinates are included so the map can
     * fly to / centre on a chosen city without a second lookup.
     *
     * @return array<int, array{value: string, label: string, region: string, lat: float, lng: float}>
     */
    public static function options(): array
    {
        return collect(self::ALL)
            ->map(fn (array $city, string $slug): array => [
                'value' => $slug,
                'label' => $city['label'],
                'region' => $city['region'],
                'lat' => $city['lat'],
                'lng' => $city['lng'],
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * Look up a city by slug (used by the location filter).
     *
     * @return array{label: string, lat: float, lng: float, region: string}|null
     */
    public static function find(string $slug): ?array
    {
        return self::ALL[$slug] ?? null;
    }

    /**
     * Human-readable label for a coordinate, snapped to the nearest known city.
     */
    public static function nearestLabel(?float $latitude, ?float $longitude): string
    {
        if ($latitude === null || $longitude === null) {
            return 'Location unavailable';
        }

        $cities = array_values(self::ALL);
        $closest = $cities[0];
        $closestDistance = INF;

        foreach ($cities as $city) {
            $distance = self::distanceKm($latitude, $longitude, $city['lat'], $city['lng']);
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $city;
            }
        }

        return $closestDistance <= self::NEAREST_CITY_MAX_KM ? $closest['label'] : 'Location unavailable';
    }

    private static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1, sqrt($a)));
    }
}
