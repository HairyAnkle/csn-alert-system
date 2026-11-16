<?php

namespace Tests\Feature;

use App\Models\Emergency;
use App\Services\FcmNotificationService;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Messaging;
use Tests\TestCase;

class FirebaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_firebase_credentials_exist_and_are_valid_json(): void
    {
        $configured = config('firebase.projects.app.credentials') ?? config('firebase.projects.app.credentials_path');
        $this->assertIsString($configured);
        $this->assertNotSame('', trim($configured));

        $service = new FirestoreService;
        $this->assertNull($service->resolveCredentialsFilePath(base_path()));

        $trimmed = trim($configured);
        $isAbsolute = str_starts_with($trimmed, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $trimmed) === 1;
        $path = $isAbsolute ? $trimmed : base_path(ltrim($trimmed, '/\\'));

        $this->assertFalse(is_dir($path), 'Firebase credentials path must not resolve to a directory.');

        if (! is_file($path) || ! is_readable($path)) {
            $this->markTestSkipped('Live Firebase service account JSON file is not configured; set FIREBASE_CREDENTIALS to run credential file inspection.');
        }

        $this->assertFileExists($path);

        $json = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($json);
        $this->assertEquals('service_account', $json['type'] ?? null);
        $this->assertEquals('csn-paranaque', $json['project_id'] ?? null);
    }

    public function test_firebase_auth_service_can_be_resolved(): void
    {
        $auth = app('firebase.auth');
        $this->assertNotNull($auth);
        $this->assertInstanceOf(Auth::class, $auth);
    }

    public function test_firebase_messaging_service_can_be_resolved(): void
    {
        $messaging = app('firebase.messaging');
        $this->assertNotNull($messaging);
        $this->assertInstanceOf(Messaging::class, $messaging);
    }

    public function test_fcm_notification_service_sends_emergency_alert_cleanly(): void
    {
        $emergency = Emergency::factory()->make([
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'location' => 'Parañaque City Center',
        ]);

        $fcm = app(FcmNotificationService::class);
        $result = $fcm->sendEmergencyAlert($emergency);

        $this->assertTrue($result);
    }

    public function test_firebase_auth_endpoint_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/firebase', [
            'id_token' => 'invalid-mock-token-string',
        ]);

        $response->assertStatus(401);
        $this->assertStringContainsString('Invalid or expired Firebase ID token', $response->json('message'));
    }

    public function test_phone_auth_endpoint_authenticates_or_creates_guardian(): void
    {
        $response = $this->postJson('/api/auth/phone', [
            'phone' => '+639171234567',
            'name' => 'Maria Parent',
            'role' => 'guardian',
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('token'));
        $this->assertEquals('+639171234567', $response->json('user.phone'));
        $this->assertEquals('Maria Parent', $response->json('user.name'));

        $this->assertDatabaseHas('users', [
            'phone' => '+639171234567',
            'name' => 'Maria Parent',
        ]);
    }
}
