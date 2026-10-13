<?php

namespace Tests\Feature;

use App\Actions\RaiseEmergency;
use App\Models\Announcement;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebDashboardSection74Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected User $responder;

    protected User $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin.sec74@csn.gov.ph',
            'role' => 'admin',
        ]);

        $this->staff = User::factory()->create([
            'email' => 'staff.sec74@csn.gov.ph',
            'role' => 'staff',
        ]);

        $this->responder = User::factory()->create([
            'email' => 'responder.sec74@pdrrmo.gov.ph',
            'role' => 'responder',
            'unit' => 'Parañaque PDRRMO Quick Response Team 4',
            'phone' => '+63 917 888 9999',
        ]);

        $this->guardian = User::factory()->create([
            'email' => 'guardian.sec74@gmail.com',
            'role' => 'guardian',
        ]);
    }

    /**
     * Item 1: Dispatch must assign a responder and persist eta.
     */
    public function test_dispatch_assigns_responder_and_persists_eta(): void
    {
        $emergency = Emergency::factory()->create([
            'status' => 'pending',
            'responder_id' => null,
            'eta' => null,
        ]);

        $response = $this->actingAs($this->staff)
            ->patchJson(route('emergencies.dispatch', $emergency), [
                'responder_id' => $this->responder->id,
                'eta' => '6 mins',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'responding')
            ->assertJsonPath('responderId', $this->responder->id)
            ->assertJsonPath('eta', '6 mins')
            ->assertJsonPath('responder.name', $this->responder->name)
            ->assertJsonPath('responder.unit', 'Parañaque PDRRMO Quick Response Team 4')
            ->assertJsonPath('responder.eta', '6 mins');

        $emergency->refresh();
        $this->assertEquals($this->responder->id, $emergency->responder_id);
        $this->assertEquals('6 mins', $emergency->eta);
        $this->assertEquals('responding', $emergency->status);
    }

    /**
     * Item 2 & 3: Handle cancelled status and alerts with no child without crashing.
     */
    public function test_emergency_handles_cancelled_status_and_null_child(): void
    {
        $emergency = Emergency::create([
            'child_id' => null,
            'guardian_id' => $this->guardian->id,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'location' => 'Live GPS (14.47930, 121.01980)',
            'lat' => 14.4793,
            'lng' => 121.0198,
            'reported_at' => now(),
        ]);

        $frontend = $emergency->toFrontendArray();

        $this->assertEquals('cancelled', $frontend['status']);
        $this->assertNotNull($frontend['cancelledAt']);
        $this->assertNull($frontend['child']['id']);
        $this->assertEquals('No profile on file', $frontend['child']['name']);
        $this->assertEquals('??', $frontend['child']['avatarInitials']);
    }

    /**
     * Item 4: Show medical fields in slide-over, emergency_contact_phone in table, and Add Child form.
     */
    public function test_child_profiles_store_and_update_with_encrypted_medical_fields(): void
    {
        $payload = [
            'name' => 'Kiko Dela Cruz',
            'age' => 7,
            'condition' => 'Sensory Processing Disorder & Asthma',
            'address' => 'Barangay BF Homes, Parañaque',
            'guardian_name' => 'Elena Dela Cruz',
            'guardian_phone' => '+63 917 111 2222',
            'emergency_contact_name' => 'Dr. Reyes (Pulmonologist)',
            'emergency_contact_phone' => '+63 920 333 4444',
            'allergies' => 'Dust mites, Shellfish, Amoxicillin',
            'medications' => 'Salbutamol Inhaler 100mcg as needed',
            'communication_notes' => 'Prefers non-verbal sign prompts when distressed',
            'medical_notes' => 'Patient has severe bronchial reactivity to dry smoke',
        ];

        // Store
        $response = $this->actingAs($this->staff)->postJson(route('children.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('name', 'Kiko Dela Cruz')
            ->assertJsonPath('guardian_phone', '+63 917 111 2222')
            ->assertJsonPath('emergency_contact_phone', '+63 920 333 4444')
            ->assertJsonPath('allergies', 'Dust mites, Shellfish, Amoxicillin')
            ->assertJsonPath('medications', 'Salbutamol Inhaler 100mcg as needed')
            ->assertJsonPath('communication_notes', 'Prefers non-verbal sign prompts when distressed');

        $childId = $response->json('id');
        $child = ChildProfile::findOrFail($childId);

        // Verify encrypted columns at database level
        $this->assertEquals('Dust mites, Shellfish, Amoxicillin', $child->allergies);
        $this->assertEquals('Salbutamol Inhaler 100mcg as needed', $child->medications);
        $this->assertEquals('Prefers non-verbal sign prompts when distressed', $child->communication_notes);

        // Update
        $updateResponse = $this->actingAs($this->staff)->putJson(route('children.update', $child), [
            'name' => 'Kiko Dela Cruz',
            'condition' => 'Sensory Processing Disorder & Asthma',
            'allergies' => 'Dust mites, Shellfish, Amoxicillin, Peanuts',
            'emergency_contact_phone' => '+63 920 999 8888',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('allergies', 'Dust mites, Shellfish, Amoxicillin, Peanuts')
            ->assertJsonPath('emergency_contact_phone', '+63 920 999 8888');

        // View single profile (DPA audit logging)
        $showResponse = $this->actingAs($this->staff)->getJson(route('children.show', $child));
        $showResponse->assertOk()
            ->assertJsonPath('allergies', 'Dust mites, Shellfish, Amoxicillin, Peanuts');
    }

    /**
     * Item 5: Announcement composer allows admin/staff to post broadcasts to mobile inbox.
     */
    public function test_staff_and_admin_can_publish_announcements_from_web(): void
    {
        $payload = [
            'title' => 'Heat Advisory Level 3',
            'body' => 'High heat index forecast today. Ensure all CSN children stay hydrated in shaded areas.',
        ];

        $response = $this->actingAs($this->staff)->postJson(route('announcements.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('title', 'Heat Advisory Level 3')
            ->assertJsonPath('body', 'High heat index forecast today. Ensure all CSN children stay hydrated in shaded areas.');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Heat Advisory Level 3',
            'created_by' => $this->staff->id,
        ]);

        // Unauthorized user check
        $forbiddenResponse = $this->actingAs($this->guardian)->postJson(route('announcements.store'), $payload);
        $forbiddenResponse->assertForbidden();
    }

    /**
     * Item 6: Web store and API store share RaiseEmergency action with double-tap protection.
     */
    public function test_shared_raise_emergency_action(): void
    {
        Event::fake();

        $action = app(RaiseEmergency::class);

        // 1. Initial creation
        $result = $action->execute([
            'type' => 'medical_crisis',
            'lat' => 14.4793,
            'lng' => 121.0198,
        ], $this->guardian);

        $this->assertFalse($result['was_existing']);
        $this->assertEquals($this->guardian->id, $result['emergency']->guardian_id);
        $this->assertEquals('pending', $result['emergency']->status);

        // 2. Double-tap check: guardian attempts another alert while one is active
        $duplicate = $action->execute([
            'type' => 'medical_crisis',
            'lat' => 14.4795,
            'lng' => 121.0200,
        ], $this->guardian);

        $this->assertTrue($duplicate['was_existing']);
        $this->assertEquals($result['emergency']->id, $duplicate['emergency']->id);

        // 3. Web store using RaiseEmergency
        $webResponse = $this->actingAs($this->staff)->postJson(route('emergencies.store'), [
            'type' => 'seizure_sensory',
            'urgency' => 'high',
            'location' => 'CSN Center Gym, Parañaque City',
            'lat' => 14.4500,
            'lng' => 121.0100,
            'guardian_notes' => 'Mild sensory overload episode.',
        ]);

        $webResponse->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('location', 'CSN Center Gym, Parañaque City');
    }

    /**
     * Test DashboardController index renders with announcements and preloads data.
     */
    public function test_dashboard_renders_with_announcements(): void
    {
        Announcement::create([
            'title' => 'Center Maintenance Notice',
            'body' => 'Air conditioning repairs scheduled for Saturday.',
            'published_at' => now(),
            'created_by' => $this->staff->id,
        ]);

        $response = $this->actingAs($this->staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('announcements');
        $response->assertViewHas('emergencies');
    }
}
