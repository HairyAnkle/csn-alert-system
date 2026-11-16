<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'child_id' => $this->child_id,
            'child' => $this->relationLoaded('child') && $this->child ? (new ChildResource($this->child))->resolve() : null,
            'type' => $this->type,
            'urgency' => $this->urgency ?? 'high',
            'status' => $this->status, // pending | responding | arrived | resolved | cancelled
            'message' => $this->guardian_notes ?? '',
            'guardian_notes' => $this->guardian_notes ?? '',
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'location' => $this->location,
            'facility_room' => $this->facility_room,
            'reported_by_role' => $this->reported_by_role ?? 'guardian',
            'reported_by_name' => $this->relationLoaded('reporter') && $this->reporter ? $this->reporter->name : null,
            'responder_arrived_at' => $this->responder_arrived_at?->toIso8601String(),
            'responder' => $this->responder ? [
                'id' => $this->responder->id,
                'name' => $this->responder->name,
                'unit' => $this->responder->unit ?? $this->responder->name,
                'eta' => $this->eta ?? '5-10 mins',
                'phone' => $this->responder->phone ?? '',
            ] : null,
            'reported_at' => $this->reported_at?->toIso8601String() ?? $this->created_at?->toIso8601String(), // keep UTC
        ];
    }
}
