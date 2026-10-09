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
        'emergency_contact',
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age ?? '—',
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'condition' => $this->condition,
            'address' => $this->address ?? 'Parañaque City',
            'guardian_id' => $this->guardian_id,
            'guardian' => $this->guardian?->name ?? $this->guardian_name ?? '—',
            'contact' => $this->emergency_contact ?? $this->guardian?->phone ?? '—',
            'notes' => $includeSensitive ? ($this->medical_notes ?? 'No notes recorded.') : null,
            'hasNotes' => ! empty($this->medical_notes),
            'avatarInitials' => $this->avatar_initials,
            'photoPath' => $this->photo_path,
            'photoUrl' => $this->photo_url,
        ];
    }
}
