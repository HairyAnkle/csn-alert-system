<?php

namespace App\Models;

use App\Services\GeocodingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Emergency extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'urgency', 'status', 'child_id', 'location', 'responder_id', 'acknowledged_at', 'dispatched_at', 'resolved_at', 'incident_summary'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
        'responder_id',
        'reported_at',
        'acknowledged_at',
        'dispatched_at',
        'resolved_at',
        'incident_summary',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'responder_id' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
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
     * Human-readable label for emergency type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match (strtolower((string) $this->type)) {
            'medical_crisis', 'medical crisis' => 'Medical Crisis',
            'seizure_sensory', 'seizure / sensory' => 'Seizure / Sensory',
            'fire', 'fire / environmental' => 'Fire / Environmental',
            'wandering', 'wandering / elopement' => 'Wandering / Elopement',
            default => ucfirst((string) $this->type),
        };
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
            'type' => $this->type_label,
            'rawType' => $this->type ?? 'medical_crisis',
            'urgency' => $this->urgency ?? 'high',
            'status' => $this->status ?? 'pending',
            'child_id' => $this->child_id,
            'location' => $this->location,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'coords' => $this->coords,
            'guardianNotes' => $this->guardian_notes ?? 'No additional notes provided.',
            'reportedAt' => $this->reported_at_human,
            'responderId' => $this->responder_id,
            'responder' => $this->responder ? [
                'id' => $this->responder->id,
                'name' => $this->responder->name,
                'role' => $this->responder->roles->first()?->name ?? $this->responder->role ?? 'Responder',
                'phone' => $this->responder->phone ?? '—',
            ] : null,
            'acknowledgedAt' => $this->acknowledged_at?->diffForHumans(),
            'dispatchedAt' => $this->dispatched_at?->diffForHumans(),
            'resolvedAt' => $this->resolved_at?->diffForHumans(),
            'incident_summary' => $this->incident_summary,
            'child' => $this->child ? [
                'id' => $this->child->id,
                'name' => $this->child->name,
                'age' => $this->child->age ?? '—',
                'condition' => $this->child->condition,
                'avatarInitials' => $this->child->avatar_initials,
                'emergencyContact' => $this->child->emergency_contact ?? '—',
            ] : [
                'id' => null,
                'name' => 'Unknown Child',
                'age' => '—',
                'condition' => '—',
                'avatarInitials' => '??',
                'emergencyContact' => '—',
            ],
        ];
    }

    /**
     * Format resolved emergency data for Incident Reports module, table, and exports.
     *
     * @return array<string, mixed>
     */
    public function toIncidentArray(): array
    {
        $duration = 'N/A';
        if ($this->reported_at && $this->resolved_at) {
            $diffMinutes = (int) $this->reported_at->diffInMinutes($this->resolved_at);
            if ($diffMinutes < 60) {
                $duration = $diffMinutes.' mins';
            } else {
                $hours = intdiv($diffMinutes, 60);
                $mins = $diffMinutes % 60;
                $duration = $hours.' hr '.$mins.' min';
            }
        }

        return [
            'id' => 'INC-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'raw_id' => $this->id,
            'child' => $this->child?->name ?? 'Unknown',
            'child_condition' => $this->child?->condition ?? '—',
            'type' => $this->type_label ?? ucfirst(str_replace('_', ' ', $this->type ?? 'Incident')),
            'raw_type' => $this->type ?? 'medical_crisis',
            'urgency' => $this->urgency ?? 'high',
            'location' => $this->location,
            'resolvedIn' => $duration,
            'unit' => $this->responder?->name ?? 'PDRRMO Emergency Unit',
            'date' => $this->resolved_at?->format('M d, Y h:i A') ?? $this->updated_at->format('M d, Y h:i A'),
            'resolved_date' => $this->resolved_at?->toDateString() ?? $this->updated_at->toDateString(),
            'incident_summary' => $this->incident_summary ?: 'No summary filed yet.',
            'raw_summary' => $this->incident_summary ?? '',
        ];
    }
}
