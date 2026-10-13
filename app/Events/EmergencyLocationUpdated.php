<?php

namespace App\Events;

use App\Models\Emergency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue connection to use when broadcasting.
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
            'id' => $this->emergency->id,
            'lat' => $this->emergency->lat !== null ? (float) $this->emergency->lat : null,
            'lng' => $this->emergency->lng !== null ? (float) $this->emergency->lng : null,
            'location' => $this->emergency->location,
        ];
    }
}
