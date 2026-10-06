<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChildProfile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'age',
        'condition',
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
     * Serialize to array matching Alpine frontend format.
     *
     * @return array<string, mixed>
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age ?? '—',
            'condition' => $this->condition,
            'guardian' => $this->guardian?->name ?? $this->guardian_name ?? '—',
            'contact' => $this->emergency_contact ?? $this->guardian?->phone ?? '—',
            'notes' => $this->medical_notes ?? 'No notes recorded.',
            'avatarInitials' => $this->avatar_initials,
        ];
    }
}
