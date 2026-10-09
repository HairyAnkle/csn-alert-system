<?php

namespace App\Http\Controllers;

use App\Events\EmergencyRaised;
use App\Models\Emergency;
use App\Models\User;
use App\Services\SmsNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmergencyController extends Controller
{
    /**
     * Display a listing of emergencies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Emergency::with(['child', 'responder'])->latest('reported_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', $request->query('urgency'));
        }

        $emergencies = $query->get()->map->toFrontendArray()->values();

        return response()->json($emergencies);
    }

    /**
     * Store a newly created emergency alert and broadcast to Reverb.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'child_id' => ['required', 'exists:child_profiles,id'],
            'type' => ['required', 'string', 'max:100'],
            'urgency' => ['required', 'in:high,low'],
            'location' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'guardian_notes' => ['nullable', 'string', 'max:1000'],
            'responder_id' => ['nullable', 'exists:users,id'],
        ]);

        $emergency = Emergency::create([
            'child_id' => $validated['child_id'],
            'type' => $validated['type'],
            'urgency' => $validated['urgency'],
            'status' => 'pending',
            'location' => $validated['location'],
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'guardian_notes' => $validated['guardian_notes'] ?? null,
            'responder_id' => $validated['responder_id'] ?? null,
            'reported_at' => now(),
        ]);

        $emergency->load(['child', 'responder']);

        // Broadcast real-time event to Reverb listeners (fail-safe if Reverb daemon is offline)
        try {
            EmergencyRaised::dispatch($emergency);
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcasting unavailable: '.$e->getMessage());
        }

        return response()->json($emergency->toFrontendArray(), 201);
    }

    /**
     * Acknowledge an active emergency alert.
     */
    public function acknowledge(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeManagement($request->user());

        $emergency->update([
            'status' => 'responding',
            'acknowledged_at' => now(),
        ]);

        $emergency->load(['child', 'responder']);

        return response()->json($emergency->toFrontendArray());
    }

    /**
     * Dispatch emergency responder unit and notify via SMS.
     */
    public function dispatch(Request $request, Emergency $emergency, SmsNotificationService $smsService): JsonResponse
    {
        $this->authorizeManagement($request->user());

        $validated = $request->validate([
            'responder_id' => ['nullable', 'exists:users,id'],
        ]);

        $responderId = $validated['responder_id'] ?? $emergency->responder_id;

        if (! $responderId && $request->user()?->hasRole('responder')) {
            $responderId = $request->user()->id;
        }

        $emergency->update([
            'status' => 'responding',
            'dispatched_at' => now(),
            'responder_id' => $responderId,
        ]);

        $emergency->load(['child', 'responder']);

        // Send SMS notification to dispatched responder unit
        $smsService->notifyDispatchedUnit($emergency);

        return response()->json($emergency->toFrontendArray());
    }

    /**
     * Mark an emergency as resolved.
     */
    public function resolve(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeManagement($request->user());

        $validated = $request->validate([
            'incident_summary' => ['nullable', 'string', 'max:5000'],
        ]);

        $updates = [
            'status' => 'resolved',
            'resolved_at' => now(),
        ];

        if (isset($validated['incident_summary'])) {
            $updates['incident_summary'] = $validated['incident_summary'];
        }

        $emergency->update($updates);

        $emergency->load(['child', 'responder']);

        return response()->json($emergency->toFrontendArray());
    }

    /**
     * Check if user is authorized to manage emergency alerts.
     */
    protected function authorizeManagement(?User $user): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $allowedRoles = ['admin', 'staff', 'responder'];

        $hasSpatieRole = $user->hasAnyRole($allowedRoles);
        $hasColumnRole = in_array(strtolower((string) $user->role), $allowedRoles, true);

        if (! $hasSpatieRole && ! $hasColumnRole) {
            abort(403, 'You do not have permission to manage emergency alerts.');
        }
    }
}
