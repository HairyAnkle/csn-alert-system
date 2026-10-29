<?php

use App\Models\ChildProfile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ChildProfile::all()->each(function (ChildProfile $child) {
            $notes = $child->medical_notes;
            if (is_string($notes) && str_starts_with(trim($notes), '{') && str_ends_with(trim($notes), '}')) {
                $decoded = json_decode($notes, true);
                if (is_array($decoded)) {
                    if (empty($child->allergies) && ! empty($decoded['allergies'])) {
                        $child->allergies = $decoded['allergies'];
                    }
                    if (empty($child->medications) && ! empty($decoded['medications'])) {
                        $child->medications = $decoded['medications'];
                    }
                    if (empty($child->communication_notes) && ! empty($decoded['communication_notes'])) {
                        $child->communication_notes = $decoded['communication_notes'];
                    }
                    if (empty($child->guardian_phone) && ! empty($decoded['guardian_phone'])) {
                        $child->guardian_phone = $decoded['guardian_phone'];
                    }
                    if (empty($child->emergency_contact_name) && ! empty($decoded['emergency_contact_name'])) {
                        $child->emergency_contact_name = $decoded['emergency_contact_name'];
                    }
                    if (empty($child->emergency_contact_phone) && ! empty($decoded['emergency_contact_phone'])) {
                        $child->emergency_contact_phone = $decoded['emergency_contact_phone'];
                    }

                    $realNotes = $decoded['medical_notes']
                        ?? $decoded['notes']
                        ?? $decoded['clinical_notes']
                        ?? null;

                    $child->medical_notes = $realNotes;
                    $child->saveQuietly();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cleanup migration is irreversible
    }
};
