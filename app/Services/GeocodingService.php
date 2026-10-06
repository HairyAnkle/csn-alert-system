<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Known Parañaque landmarks and barangays coordinates for fast matching and offline fallback.
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    protected array $paranaqueKnownLocations = [
        'bf homes' => ['lat' => 14.4532, 'lng' => 121.0189],
        'san antonio' => ['lat' => 14.4826, 'lng' => 121.0264],
        'valley 1' => ['lat' => 14.4826, 'lng' => 121.0264],
        'moonwalk' => ['lat' => 14.4981, 'lng' => 121.0125],
        'don bosco' => ['lat' => 14.4889, 'lng' => 121.0381],
        'better living' => ['lat' => 14.4889, 'lng' => 121.0381],
        'baclaran' => ['lat' => 14.5312, 'lng' => 120.9934],
        'san dionisio' => ['lat' => 14.4756, 'lng' => 120.9928],
        'tambo' => ['lat' => 14.5150, 'lng' => 120.9961],
        'sto. nino' => ['lat' => 14.5052, 'lng' => 121.0028],
        'sto nino' => ['lat' => 14.5052, 'lng' => 121.0028],
        'sun valley' => ['lat' => 14.4921, 'lng' => 121.0412],
        'sucat' => ['lat' => 14.4503, 'lng' => 121.0478],
        'don galo' => ['lat' => 14.5022, 'lng' => 120.9892],
        'la huerta' => ['lat' => 14.4947, 'lng' => 120.9881],
        'vitalez' => ['lat' => 14.5208, 'lng' => 121.0003],
        'merville' => ['lat' => 14.5098, 'lng' => 121.0268],
        'marcelo green' => ['lat' => 14.4691, 'lng' => 121.0392],
        'san isidro' => ['lat' => 14.4648, 'lng' => 121.0094],
        'city hall' => ['lat' => 14.4793, 'lng' => 121.0198],
        'kabihasnan' => ['lat' => 14.4851, 'lng' => 120.9895],
        'dr. a. santos' => ['lat' => 14.4764, 'lng' => 121.0235],
        'sucat road' => ['lat' => 14.4764, 'lng' => 121.0235],
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
}

