<?php

namespace App\Actions;

use App\Events\EmergencyRaised;
use App\Models\Emergency;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Support\Facades\Log;

class RaiseEmergency
{
    /**
     * Execute emergency creation with double-tap protection, geocoding fallback, and real-time broadcasting.
     *
     * @param  array<string, mixed>  $data
     * @return array{emergency: Emergency, was_existing: bool}
     */
    public function execute(array $data, ?User $actor = null): array
    {
        $guardianId = $data['guardian_id'] ?? null;
        if (! $guardianId && $actor && ($actor->hasRole('guardian') || strtolower((string) $actor->role) === 'guardian')) {
            $guardianId = $actor->id;
        }

        // Double-tap protection for guardians: return existing active alert if one exists
        if ($guardianId) {
            $existing = Emergency::active()
                ->where('guardian_id', $guardianId)
                ->latest('reported_at')
                ->first();

            if ($existing) {
                return [
                    'emergency' => $existing->load(['child', 'responder']),
                    'was_existing' => true,
                ];
            }
        }

        // Format location string if coordinates are provided
        $location = $data['location'] ?? null;
        if (empty($location)) {
            $location = (isset($data['lat']) && isset($data['lng']))
                ? sprintf('Live GPS (%.5f, %.5f)', (float) $data['lat'], (float) $data['lng'])
                : 'Live GPS';
        }

        // Determine child_id: from input or from actor's linked child profile
        $childId = $data['child_id'] ?? ($actor?->child?->id ?? null);

        $emergency = Emergency::create([
            'child_id' => $childId,
            'guardian_id' => $guardianId,
            'type' => $data['type'] ?? 'medical_crisis',
            'urgency' => $data['urgency'] ?? 'high',
            'status' => 'pending',
            'location' => $location,
            'lat' => isset($data['lat']) ? (float) $data['lat'] : null,
            'lng' => isset($data['lng']) ? (float) $data['lng'] : null,
            'guardian_notes' => $data['guardian_notes'] ?? null,
            'responder_id' => $data['responder_id'] ?? null,
            'eta' => $data['eta'] ?? null,
            'reported_at' => now(),
        ]);

        $emergency->load(['child', 'responder']);

        // Broadcast to Reverb listeners (fail-safe)
        try {
            EmergencyRaised::dispatch($emergency);
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcasting failed during emergency alert: '.$e->getMessage());
        }

        // Push notification via Firebase Cloud Messaging (FCM)
        try {
            app(FcmNotificationService::class)->sendEmergencyAlert($emergency);
        } catch (\Throwable $e) {
            Log::warning('FCM notification dispatch failed: '.$e->getMessage());
        }

        return [
            'emergency' => $emergency,
            'was_existing' => false,
        ];
    }
}
