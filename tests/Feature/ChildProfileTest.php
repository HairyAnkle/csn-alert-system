<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChildProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_children_endpoints(): void
    {
        $response = $this->getJson('/children');
        $response->assertStatus(401);

        $postResponse = $this->postJson('/children', [
            'name' => 'Gabriel',
            'condition' => 'Autism',
        ]);
        $postResponse->assertStatus(401);
    }

    public function test_staff_can_create_child_profile_with_encrypted_medical_notes_and_photo(): void
    {
        Storage::fake('public');

        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $photo = UploadedFile::fake()->image('mateo.jpg', 200, 200);

        $payload = [
            'name' => 'Mateo Santos',
            'age' => 8,
            'birthdate' => '2018-05-12',
            'condition' => 'Autism Spectrum Disorder',
            'address' => 'Aguirre Ave., BF Homes, Parañaque City',
            'guardian_name' => 'Carla Santos',
            'emergency_contact' => '+63 918 333 4455',
            'medical_notes' => 'Severe sound sensitivity; uses weighted blanket; non-verbal.',
            'photo' => $photo,
        ];

        $response = $this->actingAs($staff)->post('/children', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('name', 'Mateo Santos');
        $response->assertJsonPath('address', 'Aguirre Ave., BF Homes, Parañaque City');
        $response->assertJsonPath('notes', 'Severe sound sensitivity; uses weighted blanket; non-verbal.');

        $child = ChildProfile::first();
        $this->assertNotNull($child);
        $this->assertNotNull($child->photo_path);
        Storage::disk('public')->assertExists($child->photo_path);

        // Verify medical_notes is encrypted in the raw database (Data Privacy Act of 2012)
        $rawRecord = DB::table('child_profiles')->where('id', $child->id)->first();
        $this->assertNotEquals('Severe sound sensitivity; uses weighted blanket; non-verbal.', $rawRecord->medical_notes);

        // Verify model cast decrypts correctly
        $this->assertEquals('Severe sound sensitivity; uses weighted blanket; non-verbal.', $child->fresh()->medical_notes);
    }

    public function test_authenticated_user_can_search_children_by_address_name_condition(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        ChildProfile::factory()->create([
            'name' => 'Bea Cruz',
            'condition' => 'Down Syndrome',
            'address' => 'San Antonio Valley 1, Parañaque City',
        ]);

        ChildProfile::factory()->create([
            'name' => 'Daniel Padilla',
            'condition' => 'ADHD',
            'address' => 'Don Galo, Parañaque City',
        ]);

        // Search by address
        $addressSearch = $this->actingAs($staff)->getJson('/children?q=San Antonio');
        $addressSearch->assertStatus(200);
        $addressSearch->assertJsonCount(1);
        $addressSearch->assertJsonFragment(['name' => 'Bea Cruz']);

        // Search by condition
        $conditionSearch = $this->actingAs($staff)->getJson('/children?q=ADHD');
        $conditionSearch->assertStatus(200);
        $conditionSearch->assertJsonCount(1);
        $conditionSearch->assertJsonFragment(['name' => 'Daniel Padilla']);
    }

    public function test_authorized_user_can_view_full_profile_and_logs_dpa_audit(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Officer Reyes']);
        $staff->assignRole('staff');

        $child = ChildProfile::factory()->create([
            'name' => 'Lucas Gomez',
            'medical_notes' => 'Requires Epipen for peanut allergy.',
        ]);

        $response = $this->actingAs($staff)->getJson("/children/{$child->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $child->id);
        $response->assertJsonPath('notes', 'Requires Epipen for peanut allergy.');

        // Verify audit log has recorded the sensitive data view
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'dpa_audit',
            'subject_type' => ChildProfile::class,
            'subject_id' => $child->id,
            'causer_id' => $staff->id,
        ]);
    }

    public function test_unauthorized_guardian_cannot_view_another_guardians_child_profile(): void
    {
        $guardianA = User::factory()->create(['role' => 'guardian']);
        $guardianA->assignRole('guardian');

        $guardianB = User::factory()->create(['role' => 'guardian']);
        $guardianB->assignRole('guardian');

        $childB = ChildProfile::factory()->create([
            'guardian_id' => $guardianB->id,
            'medical_notes' => 'Confidential notes for Child B',
        ]);

        $response = $this->actingAs($guardianA)->getJson("/children/{$childB->id}");

        $response->assertStatus(403);
    }

    public function test_child_guardian_can_view_their_own_child_profile(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $guardian->assignRole('guardian');

        $child = ChildProfile::factory()->create([
            'guardian_id' => $guardian->id,
            'medical_notes' => 'My child needs calm environment.',
        ]);

        $response = $this->actingAs($guardian)->getJson("/children/{$child->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('notes', 'My child needs calm environment.');
    }

    public function test_staff_can_update_child_profile(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $child = ChildProfile::factory()->create([
            'address' => 'Old Address, Parañaque City',
        ]);

        $response = $this->actingAs($staff)->putJson("/children/{$child->id}", [
            'name' => $child->name,
            'condition' => $child->condition,
            'address' => 'New Address, Multinational Village, Parañaque City',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('New Address, Multinational Village, Parañaque City', $child->fresh()->address);
    }

    public function test_staff_can_delete_child_profile(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->assignRole('staff');

        $child = ChildProfile::factory()->create();

        $response = $this->actingAs($staff)->deleteJson("/children/{$child->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('child_profiles', ['id' => $child->id]);
    }
}
