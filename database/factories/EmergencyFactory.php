<?php

namespace Database\Factories;

use App\Models\ChildProfile;
use App\Models\Emergency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Emergency>
 */
class EmergencyFactory extends Factory
{
    protected $model = Emergency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => ChildProfile::factory(),
            'type' => fake()->randomElement(['medical_crisis', 'seizure_sensory', 'fire', 'wandering']),
            'urgency' => fake()->randomElement(['high', 'low']),
            'status' => 'pending',
            'location' => fake()->address().', Parañaque City',
            'lat' => 14.4793,
            'lng' => 121.0198,
            'guardian_notes' => fake()->sentence(),
            'reported_at' => now(),
        ];
    }

    public function responding(): static
    {
        return $this->state(fn () => [
            'status' => 'responding',
            'acknowledged_at' => now(),
            'dispatched_at' => now(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'resolved',
            'acknowledged_at' => now()->subMinutes(30),
            'dispatched_at' => now()->subMinutes(25),
            'resolved_at' => now(),
            'incident_summary' => fake()->paragraph(),
        ]);
    }
}
