<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance and password setup form.
     */
    public function show(Request $request, User $user): View
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        return view('auth.invitation', [
            'user' => $user,
        ]);
    }

    /**
     * Store the user's password, activate their account, and sign them in (or show mobile app notice).
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This invitation link is invalid or has expired.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ])->save();

        $role = strtolower((string) ($user->roles->first()?->name ?? $user->role ?? 'guardian'));
        if (in_array($role, ['teacher', 'guardian'], true)) {
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('invitation.activated', [
                'email' => $user->email,
                'role' => $role,
            ]);
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Show confirmation page instructing mobile app users (Teacher/Guardian) to sign in via the mobile app.
     */
    public function activated(Request $request): View
    {
        return view('auth.invitation-activated', [
            'email' => $request->query('email'),
            'role' => strtolower((string) $request->query('role', 'guardian')),
        ]);
    }
}
