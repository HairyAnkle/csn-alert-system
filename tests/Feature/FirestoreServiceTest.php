<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Emergency;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirestoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_fields_produces_correct_firestore_rest_types(): void
    {
        $service = new FirestoreService;

        $data = [
            'id' => 123,
            'urgency' => 'high',
            'is_active' => true,
            'lat' => 14.4793,
            'notes' => null,
            'child' => [
                'name' => 'John Doe',
                'age' => 8,
            ],
        ];

        $fields = $service->formatFields($data);

        $this->assertEquals(['integerValue' => '123'], $fields['id']);
        $this->assertEquals(['stringValue' => 'high'], $fields['urgency']);
        $this->assertEquals(['booleanValue' => true], $fields['is_active']);
        $this->assertEquals(['doubleValue' => 14.4793], $fields['lat']);
        $this->assertEquals(['nullValue' => null], $fields['notes']);
        $this->assertArrayHasKey('mapValue', $fields['child']);
        $this->assertEquals(
            ['stringValue' => 'John Doe'],
            $fields['child']['mapValue']['fields']['name']
        );
    }

    public function test_service_account_can_generate_valid_access_token(): void
    {
        $service = new FirestoreService;
        $token = $service->getAccessToken();

        $this->assertNotNull($token, 'OAuth access token should be fetched using ServiceAccountCredentials');
        $this->assertNotEmpty($token);
        $this->assertStringStartsWith('ya29.', $token);
    }

    public function test_sync_emergency_formats_emergency_model_cleanly(): void
    {
        $emergency = Emergency::factory()->make([
            'id' => 999,
            'type' => 'medical_crisis',
            'urgency' => 'high',
            'status' => 'pending',
            'lat' => 14.4793,
            'lng' => 121.0198,
        ]);

        $service = new FirestoreService;
        $result = $service->syncEmergency($emergency);

        $this->assertTrue($result);
    }
}
