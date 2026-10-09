<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate guardian and return Sanctum API token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! ($user->is_active ?? true) || $user->status === 'inactive') {
            return response()->json([
                'message' => 'Your account is currently deactivated. Please contact the CSN Center.',
            ], 403);
        }

        $token = $user->createToken('guardian-mobile')->plainTextToken;
        $child = $user->children()->first();

        return response()->json([
            'token' => $token,
            'guardian' => $this->formatGuardian($user),
            'child' => $this->formatChild($child, $user),
        ]);
    }

    /**
     * Register a new guardian account.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'guardian',
            'status' => 'active',
            'is_active' => true,
        ]);

        // Assign Spatie permission role if role exists
        try {
            $user->assignRole('guardian');
        } catch (\Throwable $e) {
            // Role may not be seeded or table uninitialized
        }

        $token = $user->createToken('guardian-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'guardian' => $this->formatGuardian($user),
            'child' => null,
        ], 201);
    }

    /**
     * Request a password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt sending password reset link if configured, or provide confirmation response
        try {
            $status = Password::sendResetLink($request->only('email'));
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'message' => 'Password reset link sent. Please check your email.',
                ]);
            }
        } catch (\Throwable $e) {
            // Mail driver might be in log or local test mode
        }

        return response()->json([
            'message' => 'If that email address is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Get authenticated guardian profile and associated child.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $child = $user->children()->first();

        return response()->json([
            'guardian' => $this->formatGuardian($user),
            'child' => $this->formatChild($child, $user),
        ]);
    }

    /**
     * Format guardian for mobile response.
     *
     * @return array<string, mixed>
     */
    public function formatGuardian(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'role' => $user->role ?? 'guardian',
        ];
    }

    /**
     * Format child profile for mobile app structure.
     *
     * @return array<string, mixed>|null
     */
    public function formatChild(?ChildProfile $child, ?User $guardian = null): ?array
    {
        if (! $child) {
            return null;
        }

        $notes = [];
        if (! empty($child->medical_notes)) {
            $decoded = json_decode($child->medical_notes, true);
            if (is_array($decoded)) {
                $notes = $decoded;
            } else {
                $notes = ['communication_notes' => $child->medical_notes];
            }
        }

        return [
            'id' => $child->id,
            'name' => $child->name,
            'age' => $child->age !== null ? (int) $child->age : null,
            'disability' => $child->condition ?? '',
            'allergies' => $notes['allergies'] ?? '',
            'medications' => $notes['medications'] ?? '',
            'communication_notes' => $notes['communication_notes'] ?? '',
            'guardian_phone' => $guardian?->phone ?? $notes['guardian_phone'] ?? '',
            'emergency_contact_name' => $notes['emergency_contact_name'] ?? $child->guardian_name ?? '',
            'emergency_contact_phone' => $notes['emergency_contact_phone'] ?? $child->emergency_contact ?? '',
        ];
    }
}

