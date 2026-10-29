<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\SupportMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $guardian;

    protected ChildProfile $child;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->guardian = User::factory()->create([
            'name' => 'Elena Ramos',
            'email' => 'elena.support@csn.gov.ph',
            'role' => 'guardian',
            'phone' => '+63 918 333 4455',
        ]);
        $this->guardian->assignRole('guardian');

        $this->child = ChildProfile::factory()->create([
            'name' => 'Mateo Ramos',
            'condition' => 'Autism Spectrum Disorder',
            'guardian_id' => $this->guardian->id,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'CSN Coordinator',
            'email' => 'admin.support@csn.gov.ph',
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_mobile_guardian_gets_default_welcome_message_when_thread_is_empty(): void
    {
        Sanctum::actingAs($this->guardian);

        $response = $this->getJson('/api/support/messages');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sender', 'support')
            ->assertJsonPath('data.0.sender_name', 'Parañaque CSN Desk');

        $this->assertStringContainsString(
            'Mabuhay! Welcome to Parañaque CSN Center Live Support',
            $response->json('data.0.text')
        );
    }

    public function test_mobile_guardian_can_send_support_message_and_receives_automated_acknowledgment(): void
    {
        Sanctum::actingAs($this->guardian);

        $response = $this->postJson('/api/support/messages', [
            'body' => 'Hello, I would like to ask about sensory therapy schedules for Mateo.',
            'child_id' => $this->child->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Message sent.')
            ->assertJsonPath('data.sender', 'user')
            ->assertJsonPath('data.sender_name', 'Elena Ramos')
            ->assertJsonPath('data.text', 'Hello, I would like to ask about sensory therapy schedules for Mateo.')
            ->assertJsonPath('reply.sender', 'support')
            ->assertJsonPath('reply.sender_name', 'Parañaque CSN Desk');

        $this->assertDatabaseHas('support_messages', [
            'guardian_id' => $this->guardian->id,
            'child_id' => $this->child->id,
            'sender' => 'user',
            'sender_name' => 'Elena Ramos',
            'text' => 'Hello, I would like to ask about sensory therapy schedules for Mateo.',
        ]);

        // Second message within 15 minutes does not duplicate the automated reply
        $secondResponse = $this->postJson('/api/support/messages', [
            'message' => 'Also, is the center open this Friday?',
        ]);

        $secondResponse->assertStatus(201)
            ->assertJsonPath('data.sender', 'user')
            ->assertJsonPath('data.text', 'Also, is the center open this Friday?')
            ->assertJsonPath('reply', null);

        // Suggested chat topic chip triggers the smart automated answer and saves it in database
        $suggestedResponse = $this->postJson('/api/support/messages', [
            'body' => 'CSN Center Schedule & Address',
        ]);

        $suggestedResponse->assertStatus(201)
            ->assertJsonPath('data.sender', 'user')
            ->assertJsonPath('data.text', 'CSN Center Schedule & Address')
            ->assertJsonPath('reply.sender', 'support')
            ->assertJsonPath('reply.sender_name', 'Parañaque CSN Desk');

        $this->assertStringContainsString(
            'Col. E. de Leon Street, Barangay Sto. Niño',
            $suggestedResponse->json('reply.text')
        );

        $this->assertDatabaseHas('support_messages', [
            'guardian_id' => $this->guardian->id,
            'sender' => 'support',
            'text' => $suggestedResponse->json('reply.text'),
        ]);
    }

    public function test_admin_can_view_threads_and_send_two_way_reply_visible_to_mobile_guardian(): void
    {
        SupportMessage::create([
            'guardian_id' => $this->guardian->id,
            'child_id' => $this->child->id,
            'sender' => 'user',
            'sender_name' => $this->guardian->name,
            'text' => 'Good morning CSN Desk, can I update Mateo’s medication list?',
            'is_read' => false,
        ]);

        // 1. Admin fetches threads on Web Dashboard
        $threadsResponse = $this->actingAs($this->admin)->getJson('/support/threads');
        $threadsResponse->assertOk()
            ->assertJsonFragment([
                'guardian_id' => $this->guardian->id,
                'guardian_name' => 'Elena Ramos',
                'unread_count' => 1,
            ]);

        // 2. Admin sends a live coordinator reply from Web Dashboard
        $replyResponse = $this->actingAs($this->admin)->postJson("/support/threads/{$this->guardian->id}/messages", [
            'text' => 'Good morning Mommy Elena! Yes, you can update it in the Child Profile screen or send the details here.',
            'child_id' => $this->child->id,
        ]);

        $replyResponse->assertStatus(201)
            ->assertJsonPath('data.sender', 'support')
            ->assertJsonPath('data.text', 'Good morning Mommy Elena! Yes, you can update it in the Child Profile screen or send the details here.');

        // Verify guardian's previous unread message was marked read
        $this->assertEquals(0, SupportMessage::where('guardian_id', $this->guardian->id)->where('sender', 'user')->where('is_read', false)->count());

        // 3. Guardian fetches messages on Mobile App and sees both messages in chronological order
        Sanctum::actingAs($this->guardian);
        $mobileFeed = $this->getJson('/api/support/messages');
        $mobileFeed->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.sender', 'user')
            ->assertJsonPath('data.1.sender', 'support')
            ->assertJsonPath('data.1.text', 'Good morning Mommy Elena! Yes, you can update it in the Child Profile screen or send the details here.');
    }

    public function test_guardian_cannot_access_web_admin_support_threads_endpoint(): void
    {
        $this->actingAs($this->guardian)
            ->getJson('/support/threads')
            ->assertStatus(403);
    }
}
