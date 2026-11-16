<?php

namespace App\Actions;

use App\Events\EmergencyRaised;
use App\Models\Announcement;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use App\Services\FcmNotificationService;
use App\Services\GeocodingService;
use App\Services\SmsNotificationService;
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
        $isTeacher = $actor && ($actor->hasRole('teacher') || strtolower((string) $actor->role) === 'teacher');

        // Determine child_id: from input or from actor's linked child profile (non-teacher)
        $childId = $data['child_id'] ?? ($isTeacher ? null : ($actor?->child?->id ?? null));
        $child = $childId ? ChildProfile::with('guardian')->find($childId) : null;

        $guardianId = $data['guardian_id'] ?? null;
        if (! $guardianId) {
            if ($isTeacher && $child) {
                $guardianId = $child->guardian_id;
            } elseif ($actor && ($actor->hasRole('guardian') || strtolower((string) $actor->role) === 'guardian')) {
                $guardianId = $actor->id;
            }
        }

        // Double-tap protection: return existing active alert if one already exists
        if ($isTeacher && $childId) {
            $existing = Emergency::active()
                ->where('child_id', $childId)
                ->latest('reported_at')
                ->first();

            if ($existing) {
                return [
                    'emergency' => $existing->load(['child', 'responder', 'reporter']),
                    'was_existing' => true,
                ];
            }
        } elseif (! $isTeacher && $guardianId) {
            $existing = Emergency::active()
                ->where('guardian_id', $guardianId)
                ->latest('reported_at')
                ->first();

            if ($existing) {
                return [
                    'emergency' => $existing->load(['child', 'responder', 'reporter']),
                    'was_existing' => true,
                ];
            }
        }

        // Format location string if coordinates or CSN facility/room are provided, and geocode if needed
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;
        $facilityRoom = ! empty($data['facility_room']) ? trim((string) $data['facility_room']) : null;

        $location = $data['location'] ?? null;
        if ($facilityRoom !== null && (empty($location) || ! str_contains((string) $location, $facilityRoom))) {
            $location = "CSN Center Parañaque — {$facilityRoom}";
            if ($lat === null || $lng === null) {
                $lat = 14.4997;
                $lng = 120.9949;
            }
        } elseif (empty($location) || str_starts_with((string) $location, 'Live GPS')) {
            if ($lat !== null && $lng !== null) {
                $resolvedName = app(GeocodingService::class)->reverseGeocode($lat, $lng);
                $location = $resolvedName ?: sprintf('Live GPS (%.5f, %.5f)', $lat, $lng);
            } else {
                $location = 'Live GPS';
            }
        } elseif ($lat === null || $lng === null) {
            // If location text was provided without coordinates, attempt to geocode to Parañaque landmarks
            $coords = app(GeocodingService::class)->geocode($location);
            if ($coords) {
                $lat = $coords['lat'];
                $lng = $coords['lng'];
            }
        }

        $reportedByRole = $isTeacher
            ? 'teacher'
            : ($actor ? strtolower((string) ($actor->role ?: 'guardian')) : 'guardian');

        $emergency = Emergency::create([
            'child_id' => $childId,
            'guardian_id' => $guardianId,
            'reported_by_id' => $actor?->id,
            'reported_by_role' => $reportedByRole,
            'type' => $data['type'] ?? 'medical_crisis',
            'urgency' => $data['urgency'] ?? 'high',
            'status' => 'pending',
            'location' => $location,
            'facility_room' => $facilityRoom,
            'lat' => $lat,
            'lng' => $lng,
            'guardian_notes' => $data['guardian_notes'] ?? null,
            'responder_id' => $data['responder_id'] ?? null,
            'eta' => $data['eta'] ?? null,
            'reported_at' => now(),
        ]);

        $emergency->load(['child.guardian', 'responder', 'reporter']);

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

        // If triggered by a CSN Teacher, notify the student's guardian via SMS and Mobile Inbox Announcement
        if ($isTeacher) {
            try {
                app(SmsNotificationService::class)->notifyGuardianOfTeacherAlert($emergency, $actor);
            } catch (\Throwable $e) {
                Log::warning('Guardian SMS notification failed for teacher alert: '.$e->getMessage());
            }

            try {
                Announcement::create([
                    'title' => sprintf(
                        '🚨 CSN Center Alert: %s (%s)',
                        $emergency->child?->name ?? 'Student',
                        $emergency->type_label
                    ),
                    'body' => sprintf(
                        'Reported by %s at %s.%s',
                        $actor?->name ?? 'CSN Teacher',
                        $location,
                        ! empty($emergency->guardian_notes) ? ' Notes: '.$emergency->guardian_notes : ''
                    ),
                    'published_at' => now(),
                    'created_by' => $actor?->id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Guardian inbox announcement creation failed for teacher alert: '.$e->getMessage());
            }
        }

        return [
            'emergency' => $emergency,
            'was_existing' => false,
        ];
    }
}
