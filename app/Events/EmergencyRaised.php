<?php

namespace App\Events;

use App\Models\Emergency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyRaised implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue connection to use when broadcasting.
     * Uses sync connection so broadcasts are sent immediately without delay.
     */
    public string $connection = 'sync';

    /**
     * Create a new event instance.
     */
    public function __construct(public Emergency $emergency) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('emergencies'),
        ];
    }

    /**
     * Get the data that should be broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'emergency' => $this->emergency->loadMissing(['child', 'responder'])->toFrontendArray(),
        ];
    }
}
