<?php

namespace App\Events;

use App\Models\Emergency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $connection = 'sync';

    public function __construct(public Emergency $emergency) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('emergencies'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'emergency' => $this->emergency->loadMissing(['child', 'responder'])->toFrontendArray(),
        ];
    }
}

