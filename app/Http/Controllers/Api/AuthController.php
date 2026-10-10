<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildResource;
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
     * Handle Google OAuth Sign-In (placeholder).
     */
    public function google(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Google Sign-In integration is pending client credentials setup.',
        ], 501);
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
        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'guardian' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'role' => $user->role ?? 'guardian',
            ],
            'child' => $user->child ? (new ChildResource($user->child))->resolve() : null,
        ], $status);
    }
}
