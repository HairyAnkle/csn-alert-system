<?php

namespace App\Http\Controllers\Api;

use App\Actions\RaiseEmergency;
use App\Events\EmergencyCancelled;
use App\Events\EmergencyLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmergencyResource;
use App\Models\Emergency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EmergencyApiController extends Controller
{
    /**
     * List emergencies for the authenticated guardian.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $emergencies = Emergency::where('guardian_id', $user->id)
            ->with('responder')
            ->latest('reported_at')
            ->limit(50)
            ->get();

        return response()->json([
            'emergencies' => EmergencyResource::collection($emergencies)->resolve(),
        ]);
    }

    /**
     * Trigger a new emergency alert.
     */
    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'type' => ['required', Rule::in(['medical_crisis', 'seizure_sensory', 'wandering', 'fire'])],
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $user = $request->user();
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

        $emergency->load(['child', 'responder']);

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

        $emergency->update([
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
            'location' => sprintf('Live GPS (%.5f, %.5f)', $data['lat'], $data['lng']),
        ]);

        $emergency->load(['child', 'responder']);

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

        $emergency->load(['child', 'responder']);

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
