<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianMobileApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_guardian_can_register_via_api(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Guardian',
            'email' => 'jane.guardian@example.com',
            'password' => 'secretPassword123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'guardian' => ['id', 'name', 'email', 'phone', 'role'],
                'child',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.guardian@example.com',
            'role' => 'guardian',
        ]);
    }

    public function test_guardian_can_login_and_retrieve_profile_and_child(): void
    {
        $user = User::factory()->create([
            'email' => 'login.guardian@example.com',
            'password' => bcrypt('password123'),
            'role' => 'guardian',
            'phone' => '09170001122',
        ]);

        $child = ChildProfile::create([
            'name' => 'Leo Guardian',
            'age' => 8,
            'condition' => 'Sensory Processing Disorder',
            'guardian_id' => $user->id,
            'medical_notes' => json_encode([
                'allergies' => 'Dust',
                'medications' => 'None',
                'communication_notes' => 'Sensitive to loud noise',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_phone' => '09170001122',
            ]),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login.guardian@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'guardian' => ['id', 'name', 'email'],
                'child' => ['id', 'name', 'age', 'disability', 'allergies', 'medications', 'communication_notes'],
            ]);
    }

    public function test_guardian_can_update_child_profile(): void
    {
        $user = User::factory()->create(['role' => 'guardian']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/child', [
                'name' => 'Mateo Santos',
                'age' => 10,
                'disability' => 'Autism Spectrum Disorder',
                'allergies' => 'Peanuts',
                'medications' => 'Melatonin 2mg',
                'communication_notes' => 'Uses visual schedule',
                'guardian_phone' => '09179998877',
                'emergency_contact_name' => 'Sarah Santos',
                'emergency_contact_phone' => '09189998877',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('child.name', 'Mateo Santos')
            ->assertJsonPath('child.disability', 'Autism Spectrum Disorder')
            ->assertJsonPath('child.allergies', 'Peanuts');
    }

    public function test_guardian_can_trigger_emergency_and_cancel(): void
    {
        $user = User::factory()->create(['role' => 'guardian']);
        $token = $user->createToken('test')->plainTextToken;

        // Trigger emergency
        $triggerResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/emergencies', [
                'type' => 'seizure_sensory',
                'lat' => 14.4793,
                'lng' => 121.0198,
            ]);

        $triggerResponse->assertStatus(201)
            ->assertJsonPath('emergency.type', 'seizure_sensory')
            ->assertJsonPath('emergency.status', 'pending');

        $emergencyId = $triggerResponse->json('emergency.id');

        // Cancel emergency
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/emergencies/{$emergencyId}/cancel");

        $cancelResponse->assertStatus(200)
            ->assertJsonPath('emergency.status', 'cancelled');
    }

    public function test_guardian_can_retrieve_announcements(): void
    {
        $user = User::factory()->create(['role' => 'guardian']);
        $token = $user->createToken('test')->plainTextToken;

        Announcement::create([
            'title' => 'Scheduled Drill',
            'body' => 'Annual disaster preparedness drill.',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'announcements' => [
                    '*' => ['id', 'title', 'body', 'created_at'],
                ],
            ]);
    }
}
