<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ChildApiController extends Controller
{
    /**
     * Create or update the guardian's child profile.
     */
    public function update(Request $request): JsonResponse
    {
        $d = $request->validate([
            'name' => 'required|string|max:120',
            'age' => 'required|integer|min:0|max:25',
            'disability' => 'required|string|max:120',
            'allergies' => 'nullable|string|max:2000',
            'medications' => 'nullable|string|max:2000',
            'communication_notes' => 'nullable|string|max:2000',
            'guardian_phone' => 'required|string|max:30',
            'emergency_contact_name' => 'nullable|string|max:120',
            'emergency_contact_phone' => 'required|string|max:30',
        ]);

        $user = $request->user();

        if (! empty($d['guardian_phone']) && $user->phone !== $d['guardian_phone']) {
            $user->update(['phone' => $d['guardian_phone']]);
        }

        $medicalNotesPayload = json_encode([
            'allergies' => $d['allergies'] ?? '',
            'medications' => $d['medications'] ?? '',
            'communication_notes' => $d['communication_notes'] ?? '',
            'guardian_phone' => $d['guardian_phone'] ?? '',
            'emergency_contact_name' => $d['emergency_contact_name'] ?? '',
            'emergency_contact_phone' => $d['emergency_contact_phone'] ?? '',
        ]);

        $child = $user->child()->updateOrCreate([], [
            ...Arr::except($d, ['disability']),
            'condition' => $d['disability'],
            'guardian_name' => $d['emergency_contact_name'] ?: $user->name,
            'emergency_contact' => $d['emergency_contact_phone'] ?: ($d['guardian_phone'] ?? $user->phone),
            'medical_notes' => $medicalNotesPayload,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'child' => (new ChildResource($child))->resolve(),
        ]);
    }
}
