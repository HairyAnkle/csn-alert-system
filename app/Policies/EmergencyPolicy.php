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
        if ($user->hasRole(['superadmin', 'admin', 'responder', 'teacher'])) {
            return true;
        }

        return $user->id === $emergency->guardian_id
            || $user->id === $emergency->reported_by_id
            || ($emergency->child_id && $user->children()->where('id', $emergency->child_id)->exists());
    }

    /**
     * Determine whether the user can update the emergency (e.g. streaming location).
     */
    public function update(User $user, Emergency $emergency): bool
    {
        if ($user->hasRole(['superadmin', 'admin', 'responder', 'teacher'])) {
            return true;
        }

        return $user->id === $emergency->guardian_id
            || $user->id === $emergency->reported_by_id
            || ($emergency->child_id && $user->children()->where('id', $emergency->child_id)->exists());
    }

    /**
     * Determine whether the user can cancel the emergency.
     */
    public function cancel(User $user, Emergency $emergency): bool
    {
        if ($user->hasRole(['superadmin', 'admin', 'teacher'])) {
            return true;
        }

        return $user->id === $emergency->guardian_id
            || $user->id === $emergency->reported_by_id
            || ($emergency->child_id && $user->children()->where('id', $emergency->child_id)->exists());
    }

    /**
     * Determine whether the user can confirm responder arrival on scene.
     */
    public function arrive(User $user, Emergency $emergency): bool
    {
        if ($user->hasRole(['superadmin', 'admin', 'responder', 'teacher'])) {
            return true;
        }

        return $user->id === $emergency->guardian_id
            || $user->id === $emergency->reported_by_id
            || ($emergency->child_id && $user->children()->where('id', $emergency->child_id)->exists());
    }
}
