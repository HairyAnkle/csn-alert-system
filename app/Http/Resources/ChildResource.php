<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildResource extends JsonResource
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
            'name' => $this->name,
            'age' => $this->age,
            'disability' => $this->condition,
            'condition' => $this->condition,
            'address' => $this->address ?? 'Parañaque City',
            'guardian_id' => $this->guardian_id,
            'guardian_name' => $this->relationLoaded('guardian') && $this->guardian ? $this->guardian->name : ($this->guardian_name ?? ''),
            'allergies' => $this->allergies,
            'medications' => $this->medications,
            'communication_notes' => $this->communication_notes,
            'guardian_phone' => $this->guardian_phone ?: ($this->relationLoaded('guardian') && $this->guardian ? ($this->guardian->phone ?? '') : ''),
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone ?: ($this->emergency_contact ?? ''),
            'avatar_initials' => $this->avatar_initials,
        ];
    }
}
