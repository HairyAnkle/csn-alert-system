<?php

namespace App\Models;

use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emergency extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'urgency',
        'status',
        'child_id',
        'location',
        'lat',
        'lng',
        'guardian_notes',
        'reported_at',
        'acknowledged_at',
        'dispatched_at',
        'resolved_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Emergency $emergency) {
            if (empty($emergency->lat) || empty($emergency->lng)) {
                if (! empty($emergency->location)) {
                    $coords = app(GeocodingService::class)->geocode($emergency->location);
                    if ($coords) {
                        $emergency->lat = $coords['lat'];
                        $emergency->lng = $coords['lng'];
                    }
                }
            }

            if (empty($emergency->reported_at)) {
                $emergency->reported_at = now();
            }
        });
    }

    /**
     * @return BelongsTo<ChildProfile, $this>
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class, 'child_id');
    }

    /**
     * Formatted coordinates display string (e.g., 14.4532° N, 121.0189° E).
     */
    public function getCoordsAttribute(): string
    {
        if ($this->lat === null || $this->lng === null) {
            return 'Coordinates pending';
        }

        $latDir = $this->lat >= 0 ? 'N' : 'S';
        $lngDir = $this->lng >= 0 ? 'E' : 'W';

        return sprintf('%.4f° %s, %.4f° %s', abs($this->lat), $latDir, abs($this->lng), $lngDir);
    }

    /**
     * Human-readable relative time string.
     */
    public function getReportedAtHumanAttribute(): string
    {
        return $this->reported_at?->diffForHumans() ?? 'Just now';
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
            'type' => $this->type ?? 'Emergency',
            'urgency' => $this->urgency ?? 'high',
            'status' => $this->status ?? 'pending',
            'child_id' => $this->child_id,
            'location' => $this->location,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'coords' => $this->coords,
            'guardianNotes' => $this->guardian_notes ?? 'No additional notes provided.',
            'reportedAt' => $this->reported_at_human,
            'child' => $this->child ? [
                'id' => $this->child->id,
                'name' => $this->child->name,
                'age' => $this->child->age ?? '—',
                'condition' => $this->child->condition,
                'avatarInitials' => $this->child->avatar_initials,
            ] : [
                'id' => null,
                'name' => 'Unknown Child',
                'age' => '—',
                'condition' => '—',
                'avatarInitials' => '??',
            ],
        ];
    }
}
