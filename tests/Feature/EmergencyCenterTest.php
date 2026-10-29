<?php

namespace Tests\Feature;

use App\Actions\RaiseEmergency;
use App\Events\EmergencyRaised;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use App\Services\GeocodingService;
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
        $staff = User::factory()->create(['role' => 'admin']);
        $staff->assignRole('admin');

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

        $staff = User::factory()->create(['role' => 'admin']);
        $staff->assignRole('admin');

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
        $staff = User::factory()->create(['role' => 'admin']);
        $staff->assignRole('admin');

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

        $staff = User::factory()->create(['role' => 'admin']);
        $staff->assignRole('admin');

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
        $staff = User::factory()->create(['role' => 'admin']);
        $staff->assignRole('admin');

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

    public function test_admin_or_superadmin_can_dispatch_barangay_responder_unit_with_eta(): void
    {
        $smsMock = Mockery::mock(SmsNotificationService::class);
        $smsMock->shouldReceive('notifyDispatchedUnit')
            ->once()
            ->andReturn(true);
        $this->app->instance(SmsNotificationService::class, $smsMock);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $emergency = Emergency::factory()->create([
            'status' => 'pending',
            'dispatched_at' => null,
            'responder_id' => null,
        ]);

        $response = $this->actingAs($admin)->patchJson("/emergencies/{$emergency->id}/dispatch", [
            'barangay_unit' => 'Brgy. BF Homes Emergency Response Team',
            'barangay_phone' => '(02) 8825-4411',
            'eta' => '6 mins',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'responding');
        $response->assertJsonPath('eta', '6 mins');
        $response->assertJsonPath('responder.name', 'Brgy. BF Homes Emergency Response Team');
        $response->assertJsonPath('responder.phone', '(02) 8825-4411');

        $emergency->refresh();
        $this->assertEquals('responding', $emergency->status);
        $this->assertEquals('6 mins', $emergency->eta);
        $this->assertNotNull($emergency->responder_id);
        $this->assertNotNull($emergency->dispatched_at);
    }

    public function test_alert_at_silverio_compound_geocodes_and_assigns_san_isidro_coordinates_accurately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $child = ChildProfile::factory()->create([
            'name' => 'Angelo Santos',
            'condition' => 'Sensory Processing Sensitivity',
        ]);

        $response = $this->actingAs($admin)->postJson('/emergencies', [
            'child_id' => $child->id,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'location' => 'Silverio Compound, Sucat Road, Parañaque City',
            'guardian_notes' => 'Child experiencing acute sensory crisis near Silverio Compound.',
        ]);

        $response->assertStatus(201);
        $emergency = Emergency::find($response->json('id'));

        $this->assertNotNull($emergency->lat);
        $this->assertNotNull($emergency->lng);
        // Coords should be near Silverio Compound (approx 14.478, 121.008)
        $this->assertEqualsWithDelta(14.4780, $emergency->lat, 0.005);
        $this->assertEqualsWithDelta(121.0085, $emergency->lng, 0.005);
    }

    public function test_alert_with_gps_coordinates_at_silverio_compound_reverse_geocodes_to_san_isidro(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $guardian->assignRole('guardian');

        $child = ChildProfile::factory()->create([
            'guardian_id' => $guardian->id,
            'name' => 'Carla Dalisay',
        ]);

        // Emergency raised with GPS coordinates at Silverio Compound (lat: 14.4780, lng: 121.0085) without explicit location string
        $raiseEmergency = app(RaiseEmergency::class);
        $result = $raiseEmergency->execute([
            'child_id' => $child->id,
            'type' => 'wandering',
            'urgency' => 'high',
            'lat' => 14.4780,
            'lng' => 121.0085,
        ], $guardian);

        $emergency = $result['emergency'];
        $this->assertStringContainsString('Silverio Compound', $emergency->location);
        $this->assertStringContainsString('San Isidro', $emergency->location);
        $this->assertEquals(14.4780, $emergency->lat);
        $this->assertEquals(121.0085, $emergency->lng);
    }

    public function test_geojson_point_in_polygon_allocates_exact_barangay_for_coordinates(): void
    {
        $geocodingService = app(GeocodingService::class);

        // Silverio Compound in Barangay San Isidro
        $sanIsidro = $geocodingService->findBarangayByCoordinates(14.4780, 121.0085);
        $this->assertNotNull($sanIsidro);
        $this->assertEquals('San Isidro', $sanIsidro['barangay']);
        $this->assertEquals('District 1', $sanIsidro['district']);
        $this->assertEquals('(02) 8825-6012', $sanIsidro['phone']);

        // Aguirre Avenue in Barangay BF Homes
        $bfHomes = $geocodingService->findBarangayByCoordinates(14.4532, 121.0189);
        $this->assertNotNull($bfHomes);
        $this->assertEquals('BF Homes', $bfHomes['barangay']);
        $this->assertEquals('District 2', $bfHomes['district']);
    }
}
