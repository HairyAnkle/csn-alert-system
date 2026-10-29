<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FoundationSetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_csrf_token_meta_tag_present_in_app_layout(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="csrf-token" content="', false);
    }

    public function test_roles_can_be_assigned_and_checked(): void
    {
        $roles = ['superadmin', 'admin', 'guardian', 'responder'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('responder'));

        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $this->assertTrue($responder->hasRole('responder'));
    }

    public function test_spatie_activitylog_records_model_changes(): void
    {
        $user = User::factory()->create(['name' => 'Responder Maria']);

        $child = ChildProfile::create([
            'name' => 'Gabriel Ramos',
            'age' => 9,
            'condition' => 'ADHD',
            'guardian_name' => 'Elena Ramos',
            'emergency_contact' => '+63 917 111 2233',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ChildProfile::class,
            'subject_id' => $child->id,
            'description' => 'created',
        ]);
    }

    public function test_broadcasting_and_reverb_config_are_available(): void
    {
        $this->assertNotNull(config('broadcasting.connections.reverb'));
        $this->assertNotNull(config('reverb.apps'));
    }
}
