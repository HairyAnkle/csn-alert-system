<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ChildProfile extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'age', 'birthdate', 'condition', 'address', 'guardian_name', 'emergency_contact', 'photo_path'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'age',
        'birthdate',
        'condition',
        'address',
        'guardian_id',
        'guardian_name',
        'guardian_phone',
        'emergency_contact',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'medications',
        'communication_notes',
        'medical_notes',
        'photo_path',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'age' => 'integer',
        'birthdate' => 'date',
        'medical_notes' => 'encrypted',
        'allergies' => 'encrypted',
        'medications' => 'encrypted',
        'communication_notes' => 'encrypted',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Emergency, $this>
     */
    public function emergencies(): HasMany
    {
        return $this->hasMany(Emergency::class, 'child_id');
    }

    /**
     * Compute initials for avatar (e.g., "Joshua Santos" -> "JS").
     */
    public function getAvatarInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= mb_substr($w, 0, 1);
        }

        return strtoupper($initials ?: 'CS');
    }

    /**
     * Public storage URL for the child photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.$this->photo_path);
    }

    /**
     * Serialize to array matching Alpine frontend format.
     *
     * @return array<string, mixed>
     */
    public function toFrontendArray(bool $includeSensitive = true): array
    {
        $rawNotes = $this->medical_notes;
        $decodedJson = null;

        if (is_string($rawNotes) && str_starts_with(trim($rawNotes), '{') && str_ends_with(trim($rawNotes), '}')) {
            $parsed = json_decode($rawNotes, true);
            if (is_array($parsed)) {
                $decodedJson = $parsed;
            }
        }

        $clinicalNotes = null;
        if ($decodedJson !== null) {
            $clinicalNotes = $decodedJson['medical_notes']
                ?? $decodedJson['notes']
                ?? $decodedJson['clinical_notes']
                ?? null;
        } else {
            $clinicalNotes = $rawNotes;
        }

        $allergies = $this->allergies ?: ($decodedJson['allergies'] ?? null);
        $medications = $this->medications ?: ($decodedJson['medications'] ?? null);
        $communicationNotes = $this->communication_notes ?: ($decodedJson['communication_notes'] ?? null);
        $emergencyContactName = $this->emergency_contact_name ?: ($decodedJson['emergency_contact_name'] ?? null);
        $emergencyContactPhone = $this->emergency_contact_phone ?: ($decodedJson['emergency_contact_phone'] ?? null);
        $guardianPhone = $this->guardian_phone ?: ($decodedJson['guardian_phone'] ?? null);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age ?? '—',
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'condition' => $this->condition,
            'address' => $this->address ?? 'Parañaque City',
            'guardian_id' => $this->guardian_id,
            'guardian' => $this->guardian?->name ?? $this->guardian_name ?? '—',
            'guardian_phone' => $guardianPhone ?? $this->guardian?->phone ?? '—',
            'contact' => $emergencyContactPhone ?? $this->emergency_contact ?? $this->guardian?->phone ?? '—',
            'emergency_contact' => $this->emergency_contact ?? $emergencyContactPhone ?? '—',
            'emergency_contact_name' => $emergencyContactName ?? 'Primary Contact',
            'emergency_contact_phone' => $emergencyContactPhone ?? $this->emergency_contact ?? '—',
            'allergies' => $includeSensitive ? ($allergies ?? 'None reported') : null,
            'medications' => $includeSensitive ? ($medications ?? 'None recorded') : null,
            'communication_notes' => $includeSensitive ? ($communicationNotes ?? 'No communication notes') : null,
            'notes' => $includeSensitive ? ($clinicalNotes ?: 'No confidential medical notes recorded.') : null,
            'hasNotes' => ! empty($clinicalNotes) || ! empty($allergies) || ! empty($medications) || ! empty($communicationNotes),
            'avatarInitials' => $this->avatar_initials,
            'photoPath' => $this->photo_path,
            'photoUrl' => $this->photo_url,
        ];
    }
}
