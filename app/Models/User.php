<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\CausesActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'status', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use CausesActivity, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'status' => 'active',
        'role' => 'guardian',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'phone', 'status', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return HasMany<ChildProfile, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChildProfile::class, 'guardian_id');
    }

    /**
     * Format user data for frontend Alpine state.
     *
     * @return array<string, mixed>
     */
    public function toFrontendArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roles->first()?->name ?? $this->role ?? 'guardian',
            'phone' => $this->phone ?? '—',
            'status' => $this->status ?? ($this->is_active ? 'active' : 'inactive'),
            'is_active' => (bool) ($this->is_active ?? true),
            'children' => $this->relationLoaded('children')
                ? $this->children->map(fn (ChildProfile $c) => ['id' => $c->id, 'name' => $c->name])->values()
                : [],
            'child_ids' => $this->relationLoaded('children')
                ? $this->children->pluck('id')->values()->all()
                : $this->children()->pluck('id')->all(),
            'childrenCount' => $this->relationLoaded('children') ? $this->children->count() : $this->children()->count(),
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
