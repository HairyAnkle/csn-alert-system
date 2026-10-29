<?php

namespace App\Http\Controllers;

use App\Models\ChildProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChildProfileController extends Controller
{
    /**
     * Display a listing of child profiles.
     * Supports server-side search via ?q= (name, condition, address).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess($request->user());

        $query = ChildProfile::with('guardian')->latest();

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('condition', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Guardians only see their own registered children unless staff/admin/responder
        if ($this->isGuardianOnly($request->user())) {
            $query->where('guardian_id', $request->user()->id);
        }

        $children = $query->get()->map->toFrontendArray(false)->values();

        return response()->json($children);
    }

    /**
     * Store a newly created child profile.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeModification($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'birthdate' => ['nullable', 'date'],
            'condition' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'guardian_id' => ['nullable', 'exists:users,id'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'allergies' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'communication_notes' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('children', 'public');
        }

        $guardianId = $validated['guardian_id'] ?? null;
        if (! $guardianId && $this->isGuardianOnly($request->user())) {
            $guardianId = $request->user()->id;
        }

        $emergencyContact = $validated['emergency_contact_phone'] ?? $validated['emergency_contact'] ?? null;
        $emergencyContactPhone = $validated['emergency_contact_phone'] ?? $validated['emergency_contact'] ?? null;

        $child = ChildProfile::create([
            'name' => $validated['name'],
            'age' => $validated['age'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
            'condition' => $validated['condition'],
            'address' => $validated['address'] ?? null,
            'guardian_id' => $guardianId,
            'guardian_name' => $validated['guardian_name'] ?? ($guardianId ? User::find($guardianId)?->name : null),
            'guardian_phone' => $validated['guardian_phone'] ?? null,
            'emergency_contact' => $emergencyContact,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $emergencyContactPhone,
            'allergies' => $validated['allergies'] ?? null,
            'medications' => $validated['medications'] ?? null,
            'communication_notes' => $validated['communication_notes'] ?? null,
            'medical_notes' => $validated['medical_notes'] ?? null,
            'photo_path' => $photoPath,
            'created_by' => $request->user()?->id,
        ]);

        $child->load('guardian');

        return response()->json($child->toFrontendArray(true), 201);
    }

    /**
     * Display the specified child profile with full decrypted medical notes.
     * Logs view in audit log to comply with Data Privacy Act of 2012.
     */
    public function show(Request $request, ChildProfile $child): JsonResponse
    {
        $this->authorizeChildView($request->user(), $child);

        // Data Privacy Act of 2012: Audit every view of sensitive health/medical records
        activity('dpa_audit')
            ->performedOn($child)
            ->causedBy($request->user())
            ->log("Viewed full medical profile of child: {$child->name} (DPA 2012 compliance)");

        $child->load('guardian');

        return response()->json($child->toFrontendArray(true));
    }

    /**
     * Update the specified child profile in storage.
     */
    public function update(Request $request, ChildProfile $child): JsonResponse
    {
        $this->authorizeChildModification($request->user(), $child);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'birthdate' => ['nullable', 'date'],
            'condition' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'guardian_id' => ['nullable', 'exists:users,id'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'allergies' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'communication_notes' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            if ($child->photo_path) {
                Storage::disk('public')->delete($child->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('children', 'public');
        }

        if (isset($validated['emergency_contact_phone']) && ! isset($validated['emergency_contact'])) {
            $validated['emergency_contact'] = $validated['emergency_contact_phone'];
        } elseif (isset($validated['emergency_contact']) && ! isset($validated['emergency_contact_phone'])) {
            $validated['emergency_contact_phone'] = $validated['emergency_contact'];
        }

        $child->update($validated);
        $child->load('guardian');

        return response()->json($child->toFrontendArray(true));
    }

    /**
     * Remove the specified child profile from storage.
     */
    public function destroy(Request $request, ChildProfile $child): JsonResponse
    {
        $this->authorizeAdminOrStaff($request->user());

        if ($child->photo_path) {
            Storage::disk('public')->delete($child->photo_path);
        }

        $child->delete();

        return response()->json(['message' => 'Child profile deleted successfully.']);
    }

    protected function authorizeAccess(?User $user): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }
    }

    protected function authorizeModification(?User $user): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        $allowed = ['superadmin', 'admin', 'guardian'];
        if (! $user->hasAnyRole($allowed) && ! in_array(strtolower((string) $user->role), $allowed, true)) {
            abort(403, 'Unauthorized to create child profiles.');
        }
    }

    protected function authorizeChildView(?User $user, ChildProfile $child): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        // Superadmin, admin, and responders can view during dispatch/triage
        if ($user->hasAnyRole(['superadmin', 'admin', 'responder']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin', 'responder'], true)) {
            return;
        }

        // Guardians can only view their own registered children
        if ($child->guardian_id === $user->id) {
            return;
        }

        abort(403, 'Unauthorized. Data Privacy Act of 2012 limits access to authorized personnel and the assigned guardian.');
    }

    protected function authorizeChildModification(?User $user, ChildProfile $child): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if ($user->hasAnyRole(['superadmin', 'admin']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin'], true)) {
            return;
        }

        if ($child->guardian_id === $user->id) {
            return;
        }

        abort(403, 'Unauthorized to modify this child profile.');
    }

    protected function authorizeAdminOrStaff(?User $user): void
    {
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        if (! $user->hasAnyRole(['superadmin', 'admin']) && ! in_array(strtolower((string) $user->role), ['superadmin', 'admin'], true)) {
            abort(403, 'Unauthorized. Only administrators can delete child profiles.');
        }
    }

    protected function isGuardianOnly(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $isAdminOrResponder = $user->hasAnyRole(['superadmin', 'admin', 'responder']) || in_array(strtolower((string) $user->role), ['superadmin', 'admin', 'responder'], true);

        return ! $isAdminOrResponder;
    }
}
