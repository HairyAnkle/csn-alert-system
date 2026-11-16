<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChildProfile;
use App\Models\User;

class ChildProfilePolicy
{
    /**
     * Determine whether the user can view any child profiles.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the specific child profile.
     */
    public function view(User $user, ChildProfile $child): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Administrators and responders have broad operational access for triage & dispatch
        if ($user->hasAnyRole(['superadmin', 'admin', 'responder']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin', 'responder'], true)) {
            return true;
        }

        // Guardians are strictly restricted to their own linked children (Data Privacy Act compliance)
        return $child->guardian_id === $user->id;
    }

    /**
     * Determine whether the user can create child profiles.
     */
    public function create(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $allowed = ['superadmin', 'admin', 'guardian'];

        return $user->hasAnyRole($allowed) || in_array(strtolower((string) $user->role), $allowed, true);
    }

    /**
     * Determine whether the user can update the child profile.
     */
    public function update(User $user, ChildProfile $child): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Administrators can manage all profiles
        if ($user->hasAnyRole(['superadmin', 'admin']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin'], true)) {
            return true;
        }

        // Guardians can only update their own linked children
        return $child->guardian_id === $user->id;
    }

    /**
     * Determine whether the user can delete the child profile.
     */
    public function delete(User $user, ChildProfile $child): bool
    {
        if (! $user->is_active) {
            return false;
        }

        // Only administrators can delete records
        return $user->hasAnyRole(['superadmin', 'admin']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin'], true);
    }
}
