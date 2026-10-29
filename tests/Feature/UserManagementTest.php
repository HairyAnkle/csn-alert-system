<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createSuperadmin(): User
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
            'status' => 'active',
        ]);
        $superadmin->assignRole('superadmin');

        return $superadmin;
    }

    public function test_unauthenticated_user_cannot_access_user_endpoints(): void
    {
        $this->getJson('/users')->assertStatus(401);

        $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'john@csn.gov.ph',
            'role' => 'admin',
        ])->assertStatus(401);
    }

    public function test_non_superadmin_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/users')
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/users', [
                'name' => 'Jane Responder',
                'email' => 'jane@csn.gov.ph',
                'role' => 'responder',
            ])
            ->assertStatus(403);
    }

    public function test_superadmin_can_list_users_and_filter_by_role(): void
    {
        $superadmin = $this->createSuperadmin();

        $adminUser = User::factory()->create(['name' => 'Admin Member', 'role' => 'admin']);
        $adminUser->assignRole('admin');

        $responderUser = User::factory()->create(['name' => 'Rescue Unit', 'role' => 'responder']);
        $responderUser->assignRole('responder');

        $response = $this->actingAs($superadmin)->getJson('/users');
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Admin Member']);
        $response->assertJsonFragment(['name' => 'Rescue Unit']);

        $filteredResponse = $this->actingAs($superadmin)->getJson('/users?role=admin');
        $filteredResponse->assertOk();
        $filteredResponse->assertJsonFragment(['name' => 'Admin Member']);
        $filteredResponse->assertJsonMissing(['name' => 'Rescue Unit']);
    }

    public function test_superadmin_can_create_user_and_dispatches_signed_invitation_notification(): void
    {
        Notification::fake();

        $superadmin = $this->createSuperadmin();

        $payload = [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@csn.gov.ph',
            'role' => 'admin',
            'phone' => '+63 917 111 2222',
            'status' => 'active',
            'send_invite' => true,
        ];

        $response = $this->actingAs($superadmin)->postJson('/users', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Maria Santos', 'role' => 'admin']);

        $user = User::where('email', 'maria.santos@csn.gov.ph')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->is_active);

        Notification::assertSentTo($user, UserInvitationNotification::class, function ($notification) {
            return str_contains($notification->inviteUrl, 'invitation');
        });
    }

    public function test_superadmin_can_create_guardian_and_link_child_profiles(): void
    {
        $superadmin = $this->createSuperadmin();

        $child1 = ChildProfile::create([
            'name' => 'Timmy Cruz',
            'condition' => 'Autism Spectrum Disorder',
            'address' => 'San Isidro, Parañaque',
        ]);

        $child2 = ChildProfile::create([
            'name' => 'Ana Cruz',
            'condition' => 'Sensory Processing',
            'address' => 'San Isidro, Parañaque',
        ]);

        $payload = [
            'name' => 'Elena Cruz',
            'email' => 'elena.cruz@example.com',
            'role' => 'guardian',
            'phone' => '+63 920 333 4444',
            'send_invite' => false,
            'password' => 'Secret1234!',
            'child_ids' => [$child1->id, $child2->id],
        ];

        $response = $this->actingAs($superadmin)->postJson('/users', $payload);
        $response->assertStatus(201);

        $guardian = User::where('email', 'elena.cruz@example.com')->first();
        $this->assertNotNull($guardian);
        $this->assertEquals(2, $guardian->children()->count());

        $this->assertEquals($guardian->id, $child1->fresh()->guardian_id);
        $this->assertEquals($guardian->id, $child2->fresh()->guardian_id);
    }

    public function test_superadmin_can_update_user_role_status_and_linked_children(): void
    {
        $superadmin = $this->createSuperadmin();

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@csn.gov.ph',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $child = ChildProfile::create([
            'name' => 'Leo Valdez',
            'condition' => 'Down Syndrome',
        ]);

        $updatePayload = [
            'name' => 'Updated Name',
            'role' => 'guardian',
            'phone' => '+63 999 888 7777',
            'status' => 'inactive',
            'is_active' => false,
            'child_ids' => [$child->id],
        ];

        $response = $this->actingAs($superadmin)->patchJson("/users/{$user->id}", $updatePayload);
        $response->assertOk();

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('guardian', $user->role);
        $this->assertTrue($user->hasRole('guardian'));
        $this->assertFalse($user->is_active);
        $this->assertEquals($user->id, $child->fresh()->guardian_id);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'deactivated@csn.gov.ph',
            'password' => Hash::make('password123'),
            'is_active' => false,
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'deactivated@csn.gov.ph',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_session_is_terminated_by_middleware(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        // Now admin deactivates this user in the database
        $user->is_active = false;
        $user->status = 'inactive';
        $user->save();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_signed_invitation_url_allows_setting_password_and_activates_account(): void
    {
        $user = User::factory()->create([
            'email' => 'invitee@csn.gov.ph',
            'is_active' => false,
            'status' => 'inactive',
            'password' => Hash::make('temporary-unusable-pass'),
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitation.accept',
            now()->addDays(3),
            ['user' => $user->id]
        );

        // Can view invitation setup screen with valid signed URL
        $viewResponse = $this->get($signedUrl);
        $viewResponse->assertOk();
        $viewResponse->assertSee($user->email);

        // Can post new password with signature
        $postResponse = $this->post($signedUrl, [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $postResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertEquals('active', $user->status);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_unsigned_or_tampered_invitation_url_is_forbidden(): void
    {
        $user = User::factory()->create([
            'email' => 'tampered@csn.gov.ph',
        ]);

        // Accessing without signature
        $this->get("/invitation/{$user->id}")->assertStatus(403);

        // Accessing with invalid signature
        $this->get("/invitation/{$user->id}?signature=invalid123")->assertStatus(403);
    }

    public function test_superadmin_can_resend_invitation_notification(): void
    {
        Notification::fake();

        $superadmin = $this->createSuperadmin();
        $user = User::factory()->create([
            'email' => 'resend@csn.gov.ph',
        ]);

        $response = $this->actingAs($superadmin)->postJson("/users/{$user->id}/invite");
        $response->assertOk();

        Notification::assertSentTo($user, UserInvitationNotification::class);
    }

    public function test_superadmin_cannot_delete_themselves(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->deleteJson("/users/{$superadmin->id}");
        $response->assertStatus(400);

        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_superadmin_can_create_user_with_descriptive_or_variant_role_name(): void
    {
        Notification::fake();

        $superadmin = $this->createSuperadmin();

        $payload = [
            'name' => 'Aki Test',
            'email' => 'aki.test@csn.gov.ph',
            'role' => 'Client Admin (Web Command Center)',
            'phone' => '12312312321',
            'status' => 'active',
            'send_invite' => true,
        ];

        $response = $this->actingAs($superadmin)->postJson('/users', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Aki Test', 'role' => 'admin']);

        $user = User::where('email', 'aki.test@csn.gov.ph')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('admin'));
    }
}
