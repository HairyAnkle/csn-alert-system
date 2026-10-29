<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    protected function createSuperadmin(): User
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);
        $superadmin->assignRole('superadmin');

        return $superadmin;
    }

    public function test_unauthenticated_user_cannot_access_or_patch_settings(): void
    {
        $this->getJson('/settings')->assertStatus(401);
        $this->patchJson('/settings', ['sound_on_high' => false])->assertStatus(401);
    }

    public function test_guardian_cannot_access_or_patch_settings(): void
    {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $guardian->assignRole('guardian');

        $this->actingAs($guardian)->getJson('/settings')->assertStatus(403);
        $this->actingAs($guardian)->patchJson('/settings', ['sound_on_high' => false])->assertStatus(403);
    }

    public function test_admin_can_view_default_settings(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->getJson('/settings');

        $response->assertOk();
        $response->assertJson([
            'sound_on_high' => true,
            'sound_on_low' => true,
            'hotline_pdrrmo' => '(02) 8820-7377',
            'hotline_ambulance' => '911 / (02) 8825-1022',
        ]);
    }

    public function test_admin_can_patch_and_persist_settings(): void
    {
        $admin = $this->createAdmin();

        $payload = [
            'sound_on_high' => false,
            'sound_on_low' => true,
            'hotline_pdrrmo' => '(02) 8820-9999',
            'hotline_ambulance' => '911',
        ];

        $response = $this->actingAs($admin)->patchJson('/settings', $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'sound_on_high' => false,
            'sound_on_low' => true,
            'hotline_pdrrmo' => '(02) 8820-9999',
            'hotline_ambulance' => '911',
        ]);

        $this->assertEquals(false, Setting::get('sound_on_high'));
        $this->assertEquals('(02) 8820-9999', Setting::get('hotline_pdrrmo'));
    }

    public function test_superadmin_receives_audit_log_on_dashboard(): void
    {
        $superadmin = $this->createSuperadmin();

        // Cause an activity to be recorded
        $child = ChildProfile::create([
            'name' => 'Kiko Pangilinan',
            'condition' => 'ADHD',
        ]);

        $response = $this->actingAs($superadmin)->get('/');

        $response->assertOk();
        $response->assertViewHas('auditLog');

        $auditLog = $response->viewData('auditLog');
        $this->assertNotEmpty($auditLog);
    }

    public function test_non_superadmin_does_not_receive_audit_log(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk();
        $response->assertViewHas('auditLog');

        $auditLog = $response->viewData('auditLog');
        $this->assertEmpty($auditLog);
    }

    public function test_settings_blade_view_renders_correctly(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->get('/');

        $response->assertOk();
        $response->assertSee('Alert Sound Notifications');
        $response->assertSee('Emergency Hotline Configuration');
        $response->assertSee('Support Desk');
        $response->assertSee('ISO 27001 Audit Trail');
    }
}
