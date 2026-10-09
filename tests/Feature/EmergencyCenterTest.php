<?php

namespace Tests\Feature;

use App\Events\EmergencyRaised;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use App\Services\SmsNotificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class EmergencyCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_unauthenticated_requests_cannot_access_emergency_endpoints(): void
    {
        $response = $this->getJson('/emergencies');
        $response->assertStatus(401);

        $postResponse = $this->postJson('/emergencies', []);
        $postResponse->assertStatus(401);
    }

    public function test_authenticated_users_can_list_emergencies(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $emergency = Emergency::factory()->create();

        $response = $this->actingAs($staff)->getJson('/emergencies');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'id' => $emergency->id,
            'urgency' => $emergency->urgency,
        ]);
    }

    public function test_can_create_emergency_alert_and_dispatches_broadcast_event(): void
    {
        Event::fake([EmergencyRaised::class]);

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $child = ChildProfile::factory()->create([
            'name' => 'Miguel Tan',
            'condition' => 'Autism Spectrum Disorder',
        ]);

        $payload = [
            'child_id' => $child->id,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'location' => 'Dr. A. Santos Ave., Sucat, Parañaque City',
            'guardian_notes' => 'Child experiencing acute sensory crisis and wandering.',
        ];

        $response = $this->actingAs($staff)->postJson('/emergencies', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'pending');
        $response->assertJsonPath('urgency', 'high');
        $response->assertJsonPath('child.name', 'Miguel Tan');

        $this->assertDatabaseHas('emergencies', [
            'child_id' => $child->id,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'status' => 'pending',
            'location' => 'Dr. A. Santos Ave., Sucat, Parañaque City',
        ]);

        Event::assertDispatched(EmergencyRaised::class, function (EmergencyRaised $event) use ($child) {
            return $event->emergency->child_id === $child->id;
        });
    }

    public function test_emergency_can_be_created_even_if_broadcasting_server_is_offline(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $child = ChildProfile::factory()->create([
            'name' => 'Sara Santos',
            'condition' => 'Epilepsy',
        ]);

        // Simulate broadcast connection failure (e.g. Reverb server offline)
        Event::listen(EmergencyRaised::class, function () {
            throw new \RuntimeException('Pusher error: cURL error 7: Failed to connect to localhost:8080');
        });

        $payload = [
            'child_id' => $child->id,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'location' => 'San Antonio Valley, Parañaque City',
        ];

        $response = $this->actingAs($staff)->postJson('/emergencies', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('emergencies', [
            'child_id' => $child->id,
            'type' => 'medical_crisis',
        ]);
    }

    public function test_staff_or_responder_can_acknowledge_emergency(): void
    {
        $responder = User::factory()->create(['role' => 'responder']);
        $responder->assignRole('responder');

        $emergency = Emergency::factory()->create([
            'status' => 'pending',
            'acknowledged_at' => null,
        ]);

        $response = $this->actingAs($responder)->patchJson("/emergencies/{$emergency->id}/acknowledge");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'responding');

        $emergency->refresh();
        $this->assertEquals('responding', $emergency->status);
        $this->assertNotNull($emergency->acknowledged_at);
    }

    public function test_staff_or_responder_can_dispatch_responder_and_triggers_sms_notification(): void
    {
        $smsMock = Mockery::mock(SmsNotificationService::class);
        $smsMock->shouldReceive('notifyDispatchedUnit')
            ->once()
            ->andReturn(true);
        $this->app->instance(SmsNotificationService::class, $smsMock);

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $responder = User::factory()->create([
            'name' => 'Officer Reyes',
            'role' => 'responder',
            'phone' => '+63 917 555 1234',
        ]);
        $responder->assignRole('responder');

        $emergency = Emergency::factory()->create([
            'status' => 'pending',
            'dispatched_at' => null,
            'responder_id' => null,
        ]);

        $response = $this->actingAs($staff)->patchJson("/emergencies/{$emergency->id}/dispatch", [
            'responder_id' => $responder->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'responding');
        $response->assertJsonPath('responderId', $responder->id);
        $response->assertJsonPath('responder.name', 'Officer Reyes');

        $emergency->refresh();
        $this->assertEquals('responding', $emergency->status);
        $this->assertEquals($responder->id, $emergency->responder_id);
        $this->assertNotNull($emergency->dispatched_at);
    }

    public function test_staff_or_responder_can_resolve_emergency(): void
    {
        $responder = User::factory()->create(['role' => 'responder']);
        $responder->assignRole('responder');

        $emergency = Emergency::factory()->responding()->create();

        $response = $this->actingAs($responder)->patchJson("/emergencies/{$emergency->id}/resolve");

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'resolved');

        $emergency->refresh();
        $this->assertEquals('resolved', $emergency->status);
        $this->assertNotNull($emergency->resolved_at);
    }

    public function test_guardian_cannot_manage_emergency(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $guardian->assignRole('guardian');

        $emergency = Emergency::factory()->create(['status' => 'pending']);

        $ackResponse = $this->actingAs($guardian)->patchJson("/emergencies/{$emergency->id}/acknowledge");
        $ackResponse->assertStatus(403);

        $dispatchResponse = $this->actingAs($guardian)->patchJson("/emergencies/{$emergency->id}/dispatch");
        $dispatchResponse->assertStatus(403);

        $resolveResponse = $this->actingAs($guardian)->patchJson("/emergencies/{$emergency->id}/resolve");
        $resolveResponse->assertStatus(403);
    }

    public function test_emergency_center_view_renders_log_emergency_modal_and_controls(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $emergency = Emergency::factory()->create([
            'status' => 'pending',
            'urgency' => 'high',
        ]);

        $response = $this->actingAs($staff)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Log Emergency');
        $response->assertSee('Acknowledge Alert');
        $response->assertSee('Dispatch Responder');
        $response->assertSee('Mark Resolved');
        $response->assertSee('activeModal === \'logEmergency\'', false);
    }
}
