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
            'type' => $this->type,
            'status' => $this->status, // pending | responding | resolved | cancelled
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'location' => $this->location,
            'responder' => $this->responder ? [
                'id' => $this->responder->id,
                'name' => $this->responder->name,
                'unit' => $this->responder->unit ?? $this->responder->name,
                'eta'  => $this->eta ?? '5-10 mins',
                'phone' => $this->responder->phone ?? '',
            ] : null,
            'reported_at' => $this->reported_at?->toIso8601String() ?? $this->created_at?->toIso8601String(), // keep UTC
        ];
    }
}

