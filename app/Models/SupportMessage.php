<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'guardian_id',
        'child_id',
        'admin_id',
        'sender',
        'sender_name',
        'text',
        'is_read',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
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
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Format message for Mobile API and Web Dashboard JSON responses.
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'guardian_id' => $this->guardian_id,
            'child_id' => $this->child_id,
            'admin_id' => $this->admin_id,
            'sender' => $this->sender,
            'sender_name' => $this->sender_name,
            'text' => $this->text,
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
            'time_formatted' => ($this->created_at ?? now())->timezone('Asia/Manila')->format('M d, h:i A'),
        ];
    }
}
