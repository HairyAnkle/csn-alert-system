<?php

namespace App\Http\Controllers\Api;

use App\Actions\RaiseEmergency;
use App\Events\EmergencyCancelled;
use App\Events\EmergencyLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmergencyResource;
use App\Models\Emergency;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EmergencyApiController extends Controller
{
    /**
     * List emergencies for the authenticated guardian or CSN teacher.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isTeacher = $user->hasRole('teacher') || strtolower((string) $user->role) === 'teacher';

        if ($isTeacher) {
            $emergencies = Emergency::where(function ($q) use ($user) {
                $q->where('reported_by_id', $user->id)
                    ->orWhere('reported_by_role', 'teacher');
            })
                ->with(['child.guardian', 'responder', 'reporter'])
                ->latest('reported_at')
                ->latest('id')
                ->limit(50)
                ->get();
        } else {
            $childIds = $user->children()->pluck('id');

            $emergencies = Emergency::where(function ($q) use ($user, $childIds) {
                $q->where('guardian_id', $user->id);
                if ($childIds->isNotEmpty()) {
                    $q->orWhereIn('child_id', $childIds);
                }
            })
                ->with(['child.guardian', 'responder', 'reporter'])
                ->latest('reported_at')
                ->latest('id')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'emergencies' => EmergencyResource::collection($emergencies)->resolve(),
        ]);
    }

    /**
     * Trigger a new emergency alert.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isTeacher = $user && ($user->hasRole('teacher') || strtolower((string) $user->role) === 'teacher');

        $d = $request->validate([
            'child_id' => [$isTeacher ? 'required' : 'nullable', 'exists:child_profiles,id'],
            'facility_room' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['medical_crisis', 'seizure_sensory', 'wandering', 'fire', 'general'])],
            'urgency' => ['nullable', Rule::in(['high', 'low'])],
            'message' => 'nullable|string|max:1000',
            'guardian_notes' => 'nullable|string|max:1000',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        if (! isset($d['guardian_notes']) && isset($d['message'])) {
            $d['guardian_notes'] = $d['message'];
        }

        $result = app(RaiseEmergency::class)->execute($d, $user);

        return response()->json([
            'emergency' => (new EmergencyResource($result['emergency']))->resolve(),
        ], $result['was_existing'] ? 200 : 201);
    }

    /**
     * Get details of a single emergency alert.
     */
    public function show(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorize('view', $emergency);

        $emergency->load(['child.guardian', 'responder', 'reporter']);

        return response()->json([
            'emergency' => (new EmergencyResource($emergency))->resolve(),
        ]);
    }

    /**
     * Update live coordinates while emergency is active.
     */
    public function location(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorize('update', $emergency);

        abort_unless($emergency->isActive(), 409, 'This alert is no longer active.');

        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $updates = [
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
        ];

        if (empty($emergency->location) || str_starts_with($emergency->location, 'Live GPS')) {
            $resolved = app(GeocodingService::class)->reverseGeocode((float) $data['lat'], (float) $data['lng']);
            if ($resolved) {
                $updates['location'] = $resolved;
            }
        }

        $emergency->update($updates);

        $emergency->load(['child.guardian', 'responder', 'reporter']);

        try {
            EmergencyLocationUpdated::dispatch($emergency);
        } catch (\Throwable $e) {
            Log::warning('Reverb location broadcasting failed: '.$e->getMessage());
        }

        return response()->json([
            'emergency' => (new EmergencyResource($emergency))->resolve(),
        ]);
    }

    /**
     * Confirm that the responder has arrived on scene (mobile user -> notifies admin & other mobile user).
     */
    public function arrived(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorize('arrive', $emergency);

        abort_unless($emergency->isActive(), 409, 'This alert is already resolved or cancelled.');

        $emergency->update([
            'status' => 'arrived',
            'responder_arrived_at' => $emergency->responder_arrived_at ?? now(),
            'eta' => 'Arrived on Scene',
        ]);

        $emergency->load(['child.guardian', 'responder', 'reporter']);

        return response()->json([
            'emergency' => (new EmergencyResource($emergency))->resolve(),
        ]);
    }

    /**
     * Cancel an active emergency alert (e.g. false alarm).
     */
    public function cancel(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorize('cancel', $emergency);

        abort_unless($emergency->isActive(), 409, 'This alert is already resolved or cancelled.');

        $emergency->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $emergency->load(['child.guardian', 'responder', 'reporter']);

        try {
            EmergencyCancelled::dispatch($emergency);
        } catch (\Throwable $e) {
            Log::warning('Reverb cancellation broadcasting failed: '.$e->getMessage());
        }

        return response()->json([
            'emergency' => (new EmergencyResource($emergency))->resolve(),
        ]);
    }
}
