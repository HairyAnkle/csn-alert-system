<?php

namespace App\Http\Controllers\Api;

use App\Events\EmergencyRaised;
use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use App\Models\Emergency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmergencyApiController extends Controller
{
    /**
     * List emergencies for the authenticated guardian.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $childIds = $user->children()->pluck('id');

        $emergencies = Emergency::with('responder')
            ->whereIn('child_id', $childIds)
            ->latest('reported_at')
            ->get()
            ->map(fn (Emergency $e) => $this->formatForMobile($e))
            ->values();

        return response()->json([
            'emergencies' => $emergencies,
        ]);
    }

    /**
     * Trigger a new emergency alert.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();
        $child = $user->children()->first();

        // If guardian has not created a child profile yet, create an initial placeholder
        if (! $child) {
            $child = ChildProfile::create([
                'name' => "Child of {$user->name}",
                'condition' => 'Special Needs',
                'guardian_id' => $user->id,
                'guardian_name' => $user->name,
                'emergency_contact' => $user->phone ?? '911',
                'created_by' => $user->id,
            ]);
        }

        $lat = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $lng = isset($validated['lng']) ? (float) $validated['lng'] : null;

        $locationText = ($lat && $lng)
            ? sprintf('Live GPS (%.5f, %.5f)', $lat, $lng)
            : 'Live GPS (Pending)';

        $emergency = Emergency::create([
            'child_id' => $child->id,
            'type' => $validated['type'] ?? 'medical_crisis',
            'urgency' => 'high',
            'status' => 'pending',
            'location' => $locationText,
            'lat' => $lat,
            'lng' => $lng,
            'guardian_notes' => 'Triggered via Guardian Mobile App SOS button',
            'reported_at' => now(),
        ]);

        $emergency->load(['child', 'responder']);

        // Broadcast to Reverb for real-time web dashboard update
        try {
            EmergencyRaised::dispatch($emergency);
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcasting failed during mobile SOS alert: '.$e->getMessage());
        }

        return response()->json([
            'emergency' => $this->formatForMobile($emergency),
        ], 201);
    }

    /**
     * Get details of a single emergency alert.
     */
    public function show(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeAccess($request->user(), $emergency);

        $emergency->load(['child', 'responder']);

        return response()->json([
            'emergency' => $this->formatForMobile($emergency),
        ]);
    }

    /**
     * Update live coordinates while emergency is active.
     */
    public function updateLocation(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeAccess($request->user(), $emergency);

        $validated = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];

        $emergency->update([
            'lat' => $lat,
            'lng' => $lng,
            'location' => sprintf('Live GPS (%.5f, %.5f)', $lat, $lng),
        ]);

        $emergency->load(['child', 'responder']);

        return response()->json([
            'emergency' => $this->formatForMobile($emergency),
        ]);
    }

    /**
     * Cancel an active emergency alert (e.g. false alarm).
     */
    public function cancel(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeAccess($request->user(), $emergency);

        $emergency->update([
            'status' => 'cancelled',
        ]);

        $emergency->load(['child', 'responder']);

        return response()->json([
            'emergency' => $this->formatForMobile($emergency),
        ]);
    }

    /**
     * Format emergency model for mobile contract.
     *
     * @return array<string, mixed>
     */
    public function formatForMobile(Emergency $e): array
    {
        $responder = null;
        if ($e->responder) {
            $responder = [
                'id' => $e->responder->id,
                'name' => $e->responder->name,
                'unit' => $e->responder->name,
                'eta' => '5-10 mins',
                'phone' => $e->responder->phone ?? '',
            ];
        }

        return [
            'id' => $e->id,
            'type' => $e->type,
            'status' => $e->status,
            'location' => $e->location,
            'lat' => $e->lat !== null ? (float) $e->lat : null,
            'lng' => $e->lng !== null ? (float) $e->lng : null,
            'responder' => $responder,
            'reported_at' => $e->reported_at?->toISOString() ?? $e->created_at?->toISOString(),
        ];
    }

    /**
     * Ensure guardian has permission to view/manage this emergency.
     */
    protected function authorizeAccess($user, Emergency $emergency): void
    {
        // Staff/Admin/Responder can view any emergency
        if (in_array(strtolower((string) $user->role), ['admin', 'staff', 'responder'], true)) {
            return;
        }

        $guardianChildIds = $user->children()->pluck('id')->all();
        if (! in_array($emergency->child_id, $guardianChildIds, true)) {
            abort(403, 'Unauthorized access to this emergency record.');
        }
    }
}

