<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate guardian and return Sanctum API token.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)
            || ! $user->is_active || ! $user->hasRole('guardian')) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        return $this->tokenResponse($user);
    }

    /**
     * Register a new guardian account.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'guardian',
            'status' => 'active',
            'is_active' => true,
        ]);

        try {
            $user->assignRole('guardian');
        } catch (\Throwable $e) {
            // Role may already be handled via User model
        }

        return $this->tokenResponse($user, 201);
    }

    /**
     * Request a password reset link.
     * Always returns the same message so users cannot probe which emails are registered.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // Mail transport error fallback
        }

        return response()->json([
            'message' => 'If that email address is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Handle Google OAuth / Firebase Google Sign-In from Mobile App.
     */
    public function google(Request $request): JsonResponse
    {
        $idToken = $request->input('id_token') ?? $request->input('idToken') ?? $request->input('token') ?? $request->input('credential');
        $email = $request->input('email');
        $name = $request->input('name');
        $role = $request->input('role', 'guardian');

        if ($idToken) {
            // 1. Try Firebase Auth verification
            try {
                $auth = app('firebase.auth');
                $verified = $auth->verifyIdToken($idToken);
                $email = $verified->claims()->get('email') ?? $email;
                $name = $verified->claims()->get('name') ?? $name;
            } catch (\Throwable $e) {
                // 2. Fallback to Google OAuth tokeninfo endpoint
                try {
                    $googleRes = Http::timeout(5)
                        ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

                    if ($googleRes->successful()) {
                        $googleData = $googleRes->json();
                        $email = $googleData['email'] ?? $email;
                        $name = $googleData['name'] ?? $name;
                    }
                } catch (\Throwable $gError) {
                    Log::warning('Google tokeninfo verification failed: '.$gError->getMessage());
                }
            }
        }

        if (! $email) {
            return response()->json([
                'message' => 'Google Sign-In failed: A valid id_token or email is required.',
            ], 422);
        }

        $name = ! empty(trim((string) $name)) ? trim((string) $name) : 'Google User';

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => $role,
                'status' => 'active',
                'is_active' => true,
            ]);
        } else {
            // Update name if user previously had placeholder name and a real name is now provided
            if ($name !== 'Google User' && (empty($user->name) || $user->name === 'Google User')) {
                $user->update(['name' => $name]);
            }
        }

        if (! $user->hasRole($role)) {
            try {
                $user->assignRole($role);
            } catch (\Throwable $e) {
                // Ignore if Spatie role already assigned
            }
        }

        return $this->tokenResponse($user);
    }

    /**
     * Authenticate or register a mobile guardian/responder using a Firebase ID token.
     */
    public function firebase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => 'required|string',
            'role' => 'nullable|string|in:guardian,responder',
            'phone' => 'nullable|string',
            'name' => 'nullable|string',
        ]);

        try {
            $auth = app('firebase.auth');
            $verifiedIdToken = $auth->verifyIdToken($data['id_token']);
            $uid = $verifiedIdToken->claims()->get('sub');
            $firebaseEmail = $verifiedIdToken->claims()->get('email');
            $firebasePhone = $verifiedIdToken->claims()->get('phone_number') ?? $request->input('phone');
            $email = $firebaseEmail ?? ($firebasePhone ? preg_replace('/[^0-9]/', '', $firebasePhone).'@csn.mobile' : "{$uid}@firebase.user");
            $name = $verifiedIdToken->claims()->get('name') ?? $request->input('name') ?? ($firebasePhone ? 'Guardian '.$firebasePhone : 'CSN Mobile User');
            $role = $data['role'] ?? 'guardian';

            $user = User::where('email', $email)
                ->when($firebasePhone, fn ($q) => $q->orWhere('phone', $firebasePhone))
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $firebasePhone,
                    'password' => Hash::make(Str::random(32)),
                    'role' => $role,
                    'status' => 'active',
                    'is_active' => true,
                ]);
            } else {
                if ($firebasePhone && empty($user->phone)) {
                    $user->update(['phone' => $firebasePhone]);
                }
            }

            if (! $user->hasRole($role)) {
                try {
                    $user->assignRole($role);
                } catch (\Throwable $e) {
                    // Spatie role assignment fallback
                }
            }

            return $this->tokenResponse($user);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Invalid or expired Firebase ID token: '.$e->getMessage(),
            ], 401);
        }
    }

    /**
     * Authenticate or register a guardian using mobile phone number.
     */
    public function phone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string',
            'role' => 'nullable|string|in:guardian,responder',
        ]);

        $rawPhone = preg_replace('/[^0-9+]/', '', $data['phone']);
        // Normalize Philippine mobile numbers
        $digits = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            $normalized = '0'.substr($digits, 2);
            $e164 = '+'.$digits;
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $normalized = '0'.$digits;
            $e164 = '+63'.$digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            $normalized = $digits;
            $e164 = '+63'.substr($digits, 1);
        } else {
            $normalized = $digits;
            $e164 = str_starts_with($rawPhone, '+') ? $rawPhone : '+'.$digits;
        }

        $storedPhone = str_starts_with($rawPhone, '+') ? $e164 : $normalized;
        $role = $data['role'] ?? 'guardian';
        $providedName = trim((string) ($data['name'] ?? ''));
        $name = ! empty($providedName) ? $providedName : 'Guardian '.$storedPhone;
        $email = $normalized.'@csn.mobile';

        // Match existing user by normalized phone, E.164 phone, raw phone, or phone email
        $user = User::where('phone', $normalized)
            ->orWhere('phone', $e164)
            ->orWhere('phone', $rawPhone)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'phone' => $storedPhone,
                'password' => Hash::make(Str::random(32)),
                'role' => $role,
                'status' => 'active',
                'is_active' => true,
            ]);
        } else {
            $updates = [];
            if (empty($user->phone)) {
                $updates['phone'] = $storedPhone;
            }
            if (! empty($providedName) && (empty($user->name) || str_starts_with($user->name, 'Guardian '))) {
                $updates['name'] = $providedName;
            }
            if (! empty($updates)) {
                $user->update($updates);
            }
        }

        if (! $user->hasRole($role)) {
            try {
                $user->assignRole($role);
            } catch (\Throwable $e) {
                // Spatie role assignment fallback
            }
        }

        return $this->tokenResponse($user);
    }

    /**
     * Log out guardian and revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated guardian profile and associated child.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'guardian' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'role' => $user->role ?? 'guardian',
            ],
            'child' => $user->child ? (new ChildResource($user->child))->resolve() : null,
        ]);
    }

    /**
     * Build token and profile response payload.
     */
    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'role' => $user->role ?? 'guardian',
        ];

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $userData,
            'guardian' => $userData,
            'child' => $user->child ? (new ChildResource($user->child))->resolve() : null,
        ], $status);
    }
}
