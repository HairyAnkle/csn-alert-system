<?php

namespace App\Http\Controllers;

use App\Models\ChildProfile;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        $query = User::with(['roles', 'children'])->latest();

        if ($request->filled('role') && $request->role !== 'all') {
            $role = $request->role;
            $query->where(function ($q) use ($role) {
                $q->whereHas('roles', fn ($rq) => $rq->where('name', $role))
                    ->orWhere('role', $role);
            });
        }

        $users = $query->get()->map->toFrontendArray()->values();

        return response()->json($users);
    }

    /**
     * Store a newly created user and optionally send signed invitation link.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:admin,staff,responder,guardian'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'send_invite' => ['nullable', 'boolean'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['exists:child_profiles,id'],
        ]);

        $isActive = $validated['is_active'] ?? ($validated['status'] ?? 'active') === 'active';
        $status = $isActive ? 'active' : 'inactive';
        $password = ! empty($validated['password'])
            ? Hash::make($validated['password'])
            : Hash::make(Str::random(32));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'status' => $status,
            'is_active' => $isActive,
            'password' => $password,
        ]);

        $user->assignRole($validated['role']);

        if (! empty($validated['child_ids'])) {
            ChildProfile::whereIn('id', $validated['child_ids'])->update([
                'guardian_id' => $user->id,
                'guardian_name' => $user->name,
            ]);
        }

        $sendInvite = $request->boolean('send_invite', true);
        if ($sendInvite || empty($validated['password'])) {
            $inviteUrl = URL::temporarySignedRoute('invitation.accept', now()->addDays(3), ['user' => $user->id]);
            $user->notify(new UserInvitationNotification($inviteUrl));
        }

        $user->load(['roles', 'children']);

        return response()->json($user->toFrontendArray(), 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        $user->load(['roles', 'children']);

        return response()->json($user->toFrontendArray());
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['nullable', 'string', 'in:admin,staff,responder,guardian'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'is_active' => ['nullable', 'boolean'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['exists:child_profiles,id'],
        ]);

        $updates = [];
        if (isset($validated['name'])) {
            $updates['name'] = $validated['name'];
        }
        if (isset($validated['email'])) {
            $updates['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $updates['phone'] = $validated['phone'];
        }

        if (isset($validated['status'])) {
            $updates['status'] = $validated['status'];
            $updates['is_active'] = $validated['status'] === 'active';
        } elseif (isset($validated['is_active'])) {
            $updates['is_active'] = (bool) $validated['is_active'];
            $updates['status'] = $validated['is_active'] ? 'active' : 'inactive';
        }

        if (! empty($validated['role'])) {
            $updates['role'] = $validated['role'];
            $user->syncRoles([$validated['role']]);
        }

        $user->update($updates);

        // Link guardian to children if provided
        if ($request->has('child_ids')) {
            ChildProfile::where('guardian_id', $user->id)->update(['guardian_id' => null]);
            if (! empty($validated['child_ids'])) {
                ChildProfile::whereIn('id', $validated['child_ids'])->update([
                    'guardian_id' => $user->id,
                    'guardian_name' => $user->name,
                ]);
            }
        }

        $user->load(['roles', 'children']);

        return response()->json($user->toFrontendArray());
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        if ($user->id === $request->user()?->id) {
            abort(400, 'You cannot delete your own active administrator account.');
        }

        $user->delete();

        return response()->json(['message' => 'User account deleted successfully.']);
    }

    /**
     * Send or resend signed invitation link to the user.
     */
    public function invite(Request $request, User $user): JsonResponse
    {
        $this->authorizeAdmin($request->user());

        $inviteUrl = URL::temporarySignedRoute('invitation.accept', now()->addDays(3), ['user' => $user->id]);
        $user->notify(new UserInvitationNotification($inviteUrl));

        return response()->json([
            'message' => "Invitation link sent successfully to {$user->email}.",
            'inviteUrl' => $inviteUrl,
        ]);
    }

    /**
     * Ensure the requesting user has the administrator role.
     */
    protected function authorizeAdmin(?User $user): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $isAdmin = $user->hasRole('admin') || strtolower((string) $user->role) === 'admin';
        if (! $isAdmin) {
            abort(403, 'Unauthorized. Administrator access required.');
        }
    }
}
