<?php

namespace App\Policies;

use App\Models\Emergency;
use App\Models\User;

class EmergencyPolicy
{
    /**
     * Determine whether the user can view the emergency.
     */
    public function view(User $user, Emergency $emergency): bool
    {
        if (in_array(strtolower((string) $user->role), ['admin', 'staff', 'responder'], true)) {
            return true;
        }

        return $user->id === $emergency->guardian_id;
    }

    /**
     * Determine whether the user can update the emergency (e.g. streaming location).
     */
    public function update(User $user, Emergency $emergency): bool
    {
        if (in_array(strtolower((string) $user->role), ['admin', 'staff', 'responder'], true)) {
            return true;
        }

        return $user->id === $emergency->guardian_id;
    }

    /**
     * Determine whether the user can cancel the emergency.
     */
    public function cancel(User $user, Emergency $emergency): bool
    {
        if (in_array(strtolower((string) $user->role), ['admin', 'staff'], true)) {
            return true;
        }

        return $user->id === $emergency->guardian_id;
    }
}
