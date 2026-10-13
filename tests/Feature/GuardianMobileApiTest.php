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

    public function test_emergency_alert_not_blocked_when_child_is_missing(): void
    {
        $guardian = User::factory()->create([
            'role' => 'guardian',
            'unit' => 'Caregiver Team Alpha',
            'phone' => '09171234567',
        ]);
        $token = $guardian->createToken('test')->plainTextToken;

        // Guardian sends emergency without having created a child profile first
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/emergencies', [
                'type' => 'medical_crisis',
                'lat' => 14.4793,
                'lng' => 121.0198,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('emergency.type', 'medical_crisis')
            ->assertJsonPath('emergency.status', 'pending');

        $emergency = Emergency::find($response->json('emergency.id'));
        $this->assertNotNull($emergency);
        $this->assertSame($guardian->id, $emergency->guardian_id);
        $this->assertTrue($emergency->isActive());
        $this->assertTrue(Emergency::active()->where('id', $emergency->id)->exists());

        // Cancel and verify cancelled_at timestamp
        $cancel = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/emergencies/{$emergency->id}/cancel");

        $cancel->assertStatus(200);
        $emergency->refresh();
        $this->assertSame('cancelled', $emergency->status);
        $this->assertNotNull($emergency->cancelled_at);
        $this->assertFalse($emergency->isActive());
    }

    public function test_child_medical_notes_encrypted_and_user_child_relation(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $child = ChildProfile::create([
            'name' => 'Mateo Garcia',
            'age' => 8,
            'condition' => 'ASD',
            'guardian_id' => $guardian->id,
            'allergies' => 'Peanut allergy',
            'medications' => 'Melatonin',
            'communication_notes' => 'Non-verbal, prefers visual cards',
            'guardian_phone' => '09170001111',
            'emergency_contact_name' => 'Maria Garcia',
            'emergency_contact_phone' => '09180002222',
        ]);

        $this->assertTrue($guardian->child->is($child));
        $this->assertSame('Peanut allergy', $child->allergies);
        $this->assertSame('Melatonin', $child->medications);
        $this->assertSame('Non-verbal, prefers visual cards', $child->communication_notes);
    }

    public function test_guardian_can_stream_location_and_logout(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $token = $guardian->createToken('test')->plainTextToken;

        $emergency = Emergency::create([
            'guardian_id' => $guardian->id,
            'type' => 'medical_crisis',
            'status' => 'pending',
            'location' => 'Initial location',
        ]);

        // Stream location via PATCH /api/emergencies/{emergency}/location
        $locResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/emergencies/{$emergency->id}/location", [
                'lat' => 14.48123,
                'lng' => 121.02345,
            ]);

        $locResponse->assertStatus(200)
            ->assertJsonPath('emergency.lat', 14.48123)
            ->assertJsonPath('emergency.lng', 121.02345);

        // Logout via POST /api/auth/logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('message', 'Logged out successfully.');

        // Test google route
        $emptyGoogleResponse = $this->postJson('/api/auth/google');
        $emptyGoogleResponse->assertStatus(422);

        $googleResponse = $this->postJson('/api/auth/google', [
            'email' => 'google_parent@gmail.com',
            'name' => 'Google Parent',
        ]);
        $googleResponse->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'name', 'role']]);
    }

    public function test_double_tap_protection_returns_existing_active_emergency(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $token = $guardian->createToken('test')->plainTextToken;

        // First tap
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/emergencies', [
                'type' => 'medical_crisis',
                'lat' => 14.4793,
                'lng' => 121.0198,
            ]);
        $res1->assertStatus(201);
        $emergencyId = $res1->json('emergency.id');

        // Immediate second tap (double-tap)
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/emergencies', [
                'type' => 'fire',
                'lat' => 14.4800,
                'lng' => 121.0200,
            ]);

        // Must return the existing active emergency, not create a new one
        $res2->assertStatus(200)
            ->assertJsonPath('emergency.id', $emergencyId)
            ->assertJsonPath('emergency.type', 'medical_crisis');

        $this->assertSame(1, Emergency::where('guardian_id', $guardian->id)->count());
    }

    public function test_inactive_emergency_location_and_cancel_return_409(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $token = $guardian->createToken('test')->plainTextToken;

        $emergency = Emergency::create([
            'guardian_id' => $guardian->id,
            'type' => 'medical_crisis',
            'status' => 'cancelled',
            'location' => 'Old location',
        ]);

        // Attempt location update on cancelled emergency -> 409 Conflict
        $locResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/emergencies/{$emergency->id}/location", [
                'lat' => 14.50,
                'lng' => 121.05,
            ]);
        $locResponse->assertStatus(409);

        // Attempt cancel on already cancelled emergency -> 409 Conflict
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/emergencies/{$emergency->id}/cancel");
        $cancelResponse->assertStatus(409);
    }

    public function test_policy_prevents_unauthorized_guardian_from_accessing_other_emergencies(): void
    {
        $guardianA = User::factory()->create(['role' => 'guardian']);
        $tokenA = $guardianA->createToken('test')->plainTextToken;

        $guardianB = User::factory()->create(['role' => 'guardian']);

        $emergencyB = Emergency::create([
            'guardian_id' => $guardianB->id,
            'type' => 'medical_crisis',
            'status' => 'pending',
            'location' => 'Location B',
        ]);

        // Guardian A tries to view Guardian B's emergency -> 403 Forbidden
        $viewResponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->getJson("/api/emergencies/{$emergencyB->id}");
        $viewResponse->assertStatus(403);

        // Guardian A tries to cancel Guardian B's emergency -> 403 Forbidden
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$tokenA}")
            ->postJson("/api/emergencies/{$emergencyB->id}/cancel");
        $cancelResponse->assertStatus(403);
    }

    public function test_forgot_password_returns_uniform_message_for_privacy(): void
    {
        $resUnknown = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent_user_probe@example.com',
        ]);
        $resUnknown->assertStatus(200)
            ->assertJsonPath('message', 'If that email address is registered, a password reset link has been sent.');
    }
}
