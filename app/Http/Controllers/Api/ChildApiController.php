<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChildApiController extends Controller
{
    /**
     * Create or update the guardian's child profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'disability' => ['required', 'string', 'max:255'],
            'allergies' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'communication_notes' => ['nullable', 'string'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        // Update guardian phone number if provided
        if (! empty($validated['guardian_phone']) && $user->phone !== $validated['guardian_phone']) {
            $user->update(['phone' => $validated['guardian_phone']]);
        }

        $medicalNotesPayload = json_encode([
            'allergies' => $validated['allergies'] ?? '',
            'medications' => $validated['medications'] ?? '',
            'communication_notes' => $validated['communication_notes'] ?? '',
            'guardian_phone' => $validated['guardian_phone'] ?? $user->phone ?? '',
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? '',
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? '',
        ]);

        $child = $user->children()->first();

        $attributes = [
            'name' => $validated['name'],
            'age' => isset($validated['age']) ? (int) $validated['age'] : null,
            'condition' => $validated['disability'],
            'guardian_id' => $user->id,
            'guardian_name' => $validated['emergency_contact_name'] ?: $user->name,
            'emergency_contact' => $validated['emergency_contact_phone'] ?: ($validated['guardian_phone'] ?? $user->phone),
            'medical_notes' => $medicalNotesPayload,
        ];

        if ($child) {
            $child->update($attributes);
        } else {
            $attributes['created_by'] = $user->id;
            $child = ChildProfile::create($attributes);
        }

        $authController = new AuthController;

        return response()->json([
            'child' => $authController->formatChild($child, $user),
        ]);
    }
}

