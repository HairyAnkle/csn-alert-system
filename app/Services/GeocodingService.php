<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Known Parañaque landmarks and barangays coordinates for fast matching and offline fallback.
     *
     * @var array<string, array{lat: float, lng: float, name?: string, barangay?: string}>
     */
    protected array $paranaqueKnownLocations = [
        'dr. f. c. santos compound' => ['lat' => 14.4705, 'lng' => 121.0130, 'name' => 'Dr. F. C. Santos Compound, Dr. A. Santos Ave', 'barangay' => 'San Isidro'],
        'f. c. santos compound' => ['lat' => 14.4705, 'lng' => 121.0130, 'name' => 'Dr. F. C. Santos Compound, Dr. A. Santos Ave', 'barangay' => 'San Isidro'],
        'dona ninay santos road' => ['lat' => 14.4706, 'lng' => 121.0135, 'name' => 'Doña Ninay Santos Rd, Dr. F. C. Santos Compound', 'barangay' => 'San Isidro'],
        'mercury drug sucat' => ['lat' => 14.4698, 'lng' => 121.0122, 'name' => 'Dr. A. Santos Ave (near Mercury Drug / BDO)', 'barangay' => 'San Isidro'],
        'cruz compound' => ['lat' => 14.4709, 'lng' => 121.0115, 'name' => 'Cruz Compound, Dr. A. Santos Ave', 'barangay' => 'San Isidro'],
        'westmont' => ['lat' => 14.4715, 'lng' => 121.0098, 'name' => 'Westmont Village', 'barangay' => 'San Isidro'],
        'segundo mendoza' => ['lat' => 14.4702, 'lng' => 121.0142, 'name' => 'Segundo Mendoza St', 'barangay' => 'San Isidro'],
        'silverio compound' => ['lat' => 14.4780, 'lng' => 121.0085, 'name' => 'Silverio Compound', 'barangay' => 'San Isidro'],
        'silverio' => ['lat' => 14.4780, 'lng' => 121.0085, 'name' => 'Silverio Compound', 'barangay' => 'San Isidro'],
        'purok silverio' => ['lat' => 14.4780, 'lng' => 121.0085, 'name' => 'Silverio Compound', 'barangay' => 'San Isidro'],
        'greenheights' => ['lat' => 14.4682, 'lng' => 121.0178, 'name' => 'Greenheights Subdivision (Greenheights Ave)', 'barangay' => 'San Isidro'],
        'fortunata' => ['lat' => 14.4735, 'lng' => 121.0140, 'name' => 'Fortunata Village', 'barangay' => 'San Isidro'],
        'bf homes' => ['lat' => 14.4532, 'lng' => 121.0189, 'name' => 'BF Homes (Aguirre Ave)', 'barangay' => 'BF Homes'],
        'san antonio' => ['lat' => 14.4826, 'lng' => 121.0264, 'name' => 'San Antonio Valley', 'barangay' => 'San Antonio'],
        'valley 1' => ['lat' => 14.4826, 'lng' => 121.0264, 'name' => 'San Antonio Valley 1', 'barangay' => 'San Antonio'],
        'moonwalk' => ['lat' => 14.4981, 'lng' => 121.0125, 'name' => 'Moonwalk (Armstrong Ave)', 'barangay' => 'Moonwalk'],
        'don bosco' => ['lat' => 14.4889, 'lng' => 121.0381, 'name' => 'Don Bosco (Doña Soledad Ave)', 'barangay' => 'Don Bosco'],
        'better living' => ['lat' => 14.4889, 'lng' => 121.0381, 'name' => 'Better Living Subd.', 'barangay' => 'Don Bosco'],
        'baclaran' => ['lat' => 14.5312, 'lng' => 120.9934, 'name' => 'Baclaran (Redemptorist)', 'barangay' => 'Baclaran'],
        'san dionisio' => ['lat' => 14.4756, 'lng' => 120.9928, 'name' => 'San Dionisio (Evacom)', 'barangay' => 'San Dionisio'],
        'evacom' => ['lat' => 14.4756, 'lng' => 120.9928, 'name' => 'Evacom, San Dionisio', 'barangay' => 'San Dionisio'],
        'tambo' => ['lat' => 14.5150, 'lng' => 120.9961, 'name' => 'Tambo (Quirino Ave)', 'barangay' => 'Tambo'],
        'sto. nino' => ['lat' => 14.5052, 'lng' => 121.0028, 'name' => 'Santo Niño (Ninoy Aquino Ave)', 'barangay' => 'Santo Niño'],
        'sto nino' => ['lat' => 14.5052, 'lng' => 121.0028, 'name' => 'Santo Niño', 'barangay' => 'Santo Niño'],
        'sun valley' => ['lat' => 14.4921, 'lng' => 121.0412, 'name' => 'Sun Valley', 'barangay' => 'Sun Valley'],
        'sucat' => ['lat' => 14.4503, 'lng' => 121.0478, 'name' => 'Sucat Interchange', 'barangay' => 'San Antonio'],
        'don galo' => ['lat' => 14.5022, 'lng' => 120.9892, 'name' => 'Don Galo', 'barangay' => 'Don Galo'],
        'la huerta' => ['lat' => 14.4947, 'lng' => 120.9881, 'name' => 'La Huerta', 'barangay' => 'La Huerta'],
        'vitalez' => ['lat' => 14.5208, 'lng' => 121.0003, 'name' => 'Vitalez', 'barangay' => 'Vitalez'],
        'merville' => ['lat' => 14.5098, 'lng' => 121.0268, 'name' => 'Merville', 'barangay' => 'Merville'],
        'marcelo green' => ['lat' => 14.4691, 'lng' => 121.0392, 'name' => 'Marcelo Green', 'barangay' => 'Marcelo Green'],
        'san isidro' => ['lat' => 14.4801, 'lng' => 121.0085, 'name' => 'San Isidro', 'barangay' => 'San Isidro'],
        'city hall' => ['lat' => 14.4793, 'lng' => 121.0198, 'name' => 'Parañaque City Hall', 'barangay' => 'San Antonio'],
        'kabihasnan' => ['lat' => 14.4851, 'lng' => 120.9895, 'name' => 'Kabihasnan', 'barangay' => 'La Huerta'],
        'dr. a. santos' => ['lat' => 14.4764, 'lng' => 121.0235, 'name' => 'Dr. A. Santos Avenue', 'barangay' => 'San Antonio'],
        'sucat road' => ['lat' => 14.4764, 'lng' => 121.0235, 'name' => 'Sucat Road', 'barangay' => 'San Antonio'],
    ];

    /**
     * Default center coordinates for Parañaque City.
     */
    public const DEFAULT_LAT = 14.4793;

    public const DEFAULT_LNG = 121.0198;

    /**
     * Geocode an address query using Nominatim OpenStreetMap with local Parañaque fallback.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $address): ?array
    {
        $cleanAddress = trim($address);
        if ($cleanAddress === '') {
            return null;
        }

        // 1. Try online Nominatim API geocoding
        try {
            $query = $cleanAddress;
            if (! str_contains(strtolower($query), 'parañaque') && ! str_contains(strtolower($query), 'paranaque')) {
                $query .= ', Parañaque City, Metro Manila, Philippines';
            }

            $response = Http::withHeaders([
                'User-Agent' => 'ParanaqueCSNAlertSystem/1.0 (csn-alert@paranaque.gov.ph)',
                'Accept' => 'application/json',
            ])->timeout(3)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                    return [
                        'lat' => round((float) $data[0]['lat'], 7),
                        'lng' => round((float) $data[0]['lon'], 7),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocoding request failed: '.$e->getMessage());
        }

        // 2. Fallback to local landmark / barangay dictionary matching
        return $this->resolveLocalCoordinates($cleanAddress);
    }

    /**
     * Resolve coordinates from known Parañaque locations or city center fallback.
     *
     * @return array{lat: float, lng: float}
     */
    public function resolveLocalCoordinates(string $address): array
    {
        $normalized = strtolower($address);

        foreach ($this->paranaqueKnownLocations as $keyword => $coords) {
            if (str_contains($normalized, $keyword)) {
                // Add a small jitter (+- 0.001 deg ~ 100m) if generic so multiple markers don't stack perfectly on top
                $jitterLat = (mt_rand(-50, 50) / 100000);
                $jitterLng = (mt_rand(-50, 50) / 100000);

                return [
                    'lat' => round($coords['lat'] + $jitterLat, 7),
                    'lng' => round($coords['lng'] + $jitterLng, 7),
                ];
            }
        }

        // Default to Parañaque Center with slight variation
        $jitterLat = (mt_rand(-80, 80) / 100000);
        $jitterLng = (mt_rand(-80, 80) / 100000);

        return [
            'lat' => round(self::DEFAULT_LAT + $jitterLat, 7),
            'lng' => round(self::DEFAULT_LNG + $jitterLng, 7),
        ];
    }

    /**
     * Cached GeoJSON features for Parañaque barangay polygons.
     *
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $geojsonFeatures = null;

    /**
     * Load the official Parañaque 16 Barangays GeoJSON boundary dataset.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBarangayGeoJsonFeatures(): array
    {
        if ($this->geojsonFeatures !== null) {
            return $this->geojsonFeatures;
        }

        $path = storage_path('app/geojson/paranaque-barangays.geojson');
        if (! file_exists($path)) {
            $path = public_path('data/paranaque-barangays.geojson');
        }

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            $this->geojsonFeatures = $decoded['features'] ?? [];
        } else {
            $this->geojsonFeatures = [];
        }

        return $this->geojsonFeatures;
    }

    /**
     * Determine the Barangay for a given coordinate using Point-in-Polygon (PIP) ray casting.
     *
     * @return array{barangay: string, name: string, district: string, phone: string}|null
     */
    public function findBarangayByCoordinates(float $lat, float $lng): ?array
    {
        $features = $this->getBarangayGeoJsonFeatures();
        $pt = [$lng, $lat]; // GeoJSON coordinates format: [longitude, latitude]

        foreach ($features as $f) {
            $geom = $f['geometry'] ?? null;
            if (! $geom) {
                continue;
            }

            $type = $geom['type'] ?? '';
            $coords = $geom['coordinates'] ?? [];

            if ($type === 'Polygon' && ! empty($coords[0])) {
                if ($this->pointInPolygon($pt, $coords[0])) {
                    return $f['properties'];
                }
            } elseif ($type === 'MultiPolygon') {
                foreach ($coords as $poly) {
                    if (! empty($poly[0]) && $this->pointInPolygon($pt, $poly[0])) {
                        return $f['properties'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Ray-casting algorithm to test if a 2D point [lng, lat] is inside a polygon ring.
     *
     * @param  array{0: float, 1: float}  $point
     * @param  array<int, array{0: float, 1: float}>  $polygon
     */
    public function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point[0];
        $y = $point[1];
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Reverse geocode coordinates to a recognizable street, Parañaque landmark, or Barangay.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        // 1. Identify official barangay using GeoJSON Point-in-Polygon
        $pipBarangay = $this->findBarangayByCoordinates($lat, $lng);
        $barangayName = $pipBarangay['barangay'] ?? null;

        // 2. Try OpenStreetMap (Nominatim) reverse geocoding for exact street / residential compound
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ParanaqueCSNAlertSystem/1.0 (csn-alert@paranaque.gov.ph)',
                'Accept' => 'application/json',
            ])->timeout(3)->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'json',
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $addr = $data['address'] ?? [];

                $street = $addr['road'] ?? $addr['pedestrian'] ?? null;
                $neighborhood = $addr['residential'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['quarter'] ?? null;
                $resolvedBrgy = $barangayName ?? ($addr['quarter'] ?? ($addr['suburb'] ?? null));

                $parts = [];
                if (! empty($street)) {
                    $parts[] = $street;
                }
                if (! empty($neighborhood) && $neighborhood !== $street) {
                    $parts[] = $neighborhood;
                }
                if (! empty($resolvedBrgy)) {
                    $parts[] = str_starts_with($resolvedBrgy, 'Brgy.') ? $resolvedBrgy : "Brgy. {$resolvedBrgy}";
                }
                $parts[] = 'Parañaque City';

                if (count($parts) >= 2) {
                    return implode(', ', array_unique($parts));
                }
            }
        } catch (\Throwable $e) {
            Log::info('Online reverse geocoding skipped/failed, using local geographic data: '.$e->getMessage());
        }

        // 3. Fallback: Identify recognized local landmark / compound within 250m
        $closestLandmark = null;
        $minDistanceKm = INF;

        foreach ($this->paranaqueKnownLocations as $loc) {
            $dist = $this->calculateHaversineDistance($lat, $lng, $loc['lat'], $loc['lng']);
            if ($dist < $minDistanceKm) {
                $minDistanceKm = $dist;
                $closestLandmark = $loc;
            }
        }

        if ($closestLandmark && $minDistanceKm <= 0.25) {
            $name = $closestLandmark['name'] ?? 'Parañaque';
            $brgy = $barangayName ?? ($closestLandmark['barangay'] ?? 'Parañaque');

            return "{$name}, Brgy. {$brgy}, Parañaque City";
        }

        if ($barangayName) {
            $primaryRoads = [
                'San Isidro' => 'Dr. A. Santos Ave (Sucat Rd)',
                'San Antonio' => 'Dr. A. Santos Ave (Sucat Rd)',
                'Don Bosco' => 'Doña Soledad Ave',
                'BF Homes' => 'Aguirre Ave / President\'s Ave',
                'Moonwalk' => 'Armstrong Ave',
                'Sun Valley' => 'Sun Valley Dr',
                'Marcelo Green' => 'Marcelo Ave',
                'Merville' => 'West Service Rd',
                'Santo Niño' => 'Ninoy Aquino Ave',
                'La Huerta' => 'Quirino Ave / Kabihasnan Rd',
                'Tambo' => 'Quirino Ave / NAIA Rd',
                'Baclaran' => 'Roxas Blvd / Quirino Ave',
                'San Dionisio' => 'Dr. A. Santos Ave (Sucat Rd)',
                'Don Galo' => 'Quirino Ave',
                'Vitalez' => 'Ninoy Aquino Ave',
            ];

            $road = $primaryRoads[$barangayName] ?? null;

            return $road
                ? "{$road}, Brgy. {$barangayName}, Parañaque City"
                : "Brgy. {$barangayName}, Parañaque City";
        }

        return null;
    }

    /**
     * Calculate Haversine distance in kilometers between two coordinates.
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
