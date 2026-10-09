<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Authorize that the current user can manage settings.
     */
    protected function authorizeSettingsAccess(?User $user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $role = strtolower($user->roles->first()?->name ?? $user->role ?? '');

        if (! in_array($role, ['admin', 'staff'], true)) {
            abort(403, 'Unauthorized to modify system configuration.');
        }
    }

    /**
     * Get current system settings.
     */
    public function show(Request $request): JsonResponse
    {
        $this->authorizeSettingsAccess($request->user());

        return response()->json(Setting::allValues());
    }

    /**
     * Update settings configuration.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorizeSettingsAccess($request->user());

        $validated = $request->validate([
            'sound_on_high' => ['nullable', 'boolean'],
            'sound_on_low' => ['nullable', 'boolean'],
            'hotline_pdrrmo' => ['nullable', 'string', 'max:100'],
            'hotline_ambulance' => ['nullable', 'string', 'max:100'],
        ]);

        if ($request->has('sound_on_high')) {
            Setting::set('sound_on_high', $request->boolean('sound_on_high'));
        }

        if ($request->has('sound_on_low')) {
            Setting::set('sound_on_low', $request->boolean('sound_on_low'));
        }

        if (array_key_exists('hotline_pdrrmo', $validated)) {
            Setting::set('hotline_pdrrmo', $validated['hotline_pdrrmo'] ?? '');
        }

        if (array_key_exists('hotline_ambulance', $validated)) {
            Setting::set('hotline_ambulance', $validated['hotline_ambulance'] ?? '');
        }

        return response()->json([
            'message' => 'System configuration saved successfully.',
            'settings' => Setting::allValues(),
        ]);
    }
}
