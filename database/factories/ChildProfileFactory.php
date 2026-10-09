<?php

namespace Database\Factories;

use App\Models\ChildProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChildProfile>
 */
class ChildProfileFactory extends Factory
{
    protected $model = ChildProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'age' => fake()->numberBetween(3, 17),
            'condition' => fake()->randomElement(['Autism Spectrum Disorder', 'Down Syndrome', 'Cerebral Palsy', 'Epilepsy', 'ADHD']),
            'address' => fake()->streetAddress().', Parañaque City',
            'guardian_name' => fake()->name(),
            'emergency_contact' => fake()->numerify('+63 9## ### ####'),
            'medical_notes' => fake()->sentence(),
        ];
    }
}
