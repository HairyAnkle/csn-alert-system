<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    protected function createStaffUser(): User
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
        $staff->assignRole('staff');

        return $staff;
    }

    public function test_unauthenticated_user_cannot_access_incident_reporting_endpoints(): void
    {
        $this->getJson('/incident-reports')->assertStatus(401);
        $this->get('/incident-reports/export-pdf')->assertStatus(302);
        $this->get('/incident-reports/export-csv')->assertStatus(302);
    }

    public function test_guardian_cannot_access_incident_reporting_endpoints(): void
    {
        $guardian = User::factory()->create([
            'role' => 'guardian',
            'is_active' => true,
        ]);
        $guardian->assignRole('guardian');

        $this->actingAs($guardian)->getJson('/incident-reports')->assertStatus(403);
        $this->actingAs($guardian)->get('/incident-reports/export-pdf')->assertStatus(403);
        $this->actingAs($guardian)->get('/incident-reports/export-csv')->assertStatus(403);
    }

    public function test_staff_can_list_resolved_incidents_only(): void
    {
        $staff = $this->createStaffUser();

        $child = ChildProfile::factory()->create(['name' => 'Lucas Morales']);

        // Resolved incident
        $resolved = Emergency::factory()->create([
            'child_id' => $child->id,
            'status' => 'resolved',
            'type' => 'medical_crisis',
            'reported_at' => now()->subMinutes(30),
            'resolved_at' => now(),
            'incident_summary' => 'Inhaler administered, oxygen stabilized.',
        ]);

        // Pending emergency (should NOT appear in incident reports)
        $pending = Emergency::factory()->create([
            'child_id' => $child->id,
            'status' => 'pending',
            'type' => 'wandering',
        ]);

        $response = $this->actingAs($staff)->getJson('/incident-reports');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => 'INC-'.str_pad((string) $resolved->id, 4, '0', STR_PAD_LEFT),
            'child' => 'Lucas Morales',
            'incident_summary' => 'Inhaler administered, oxygen stabilized.',
        ]);
        $response->assertJsonMissing([
            'id' => 'INC-'.str_pad((string) $pending->id, 4, '0', STR_PAD_LEFT),
        ]);
    }

    public function test_date_range_and_keyword_filters_work(): void
    {
        $staff = $this->createStaffUser();

        $childA = ChildProfile::factory()->create(['name' => 'Maya Santos']);
        $childB = ChildProfile::factory()->create(['name' => 'Leo Cortez']);

        // Incident in September
        Emergency::factory()->create([
            'child_id' => $childA->id,
            'status' => 'resolved',
            'type' => 'seizure_sensory',
            'reported_at' => '2026-09-10 10:00:00',
            'resolved_at' => '2026-09-10 10:25:00',
        ]);

        // Incident in October
        Emergency::factory()->create([
            'child_id' => $childB->id,
            'status' => 'resolved',
            'type' => 'wandering',
            'reported_at' => '2026-10-05 14:00:00',
            'resolved_at' => '2026-10-05 14:40:00',
        ]);

        // Filter October only
        $octoberResponse = $this->actingAs($staff)->getJson('/incident-reports?start_date=2026-10-01&end_date=2026-10-31');
        $octoberResponse->assertOk();
        $octoberResponse->assertJsonFragment(['child' => 'Leo Cortez']);
        $octoberResponse->assertJsonMissing(['child' => 'Maya Santos']);

        // Keyword filter
        $searchResponse = $this->actingAs($staff)->getJson('/incident-reports?q=Maya');
        $searchResponse->assertOk();
        $searchResponse->assertJsonFragment(['child' => 'Maya Santos']);
        $searchResponse->assertJsonMissing(['child' => 'Leo Cortez']);
    }

    public function test_staff_can_update_incident_summary_notes(): void
    {
        $staff = $this->createStaffUser();

        $emergency = Emergency::factory()->create([
            'status' => 'resolved',
            'reported_at' => now()->subHour(),
            'resolved_at' => now(),
            'incident_summary' => null,
        ]);

        $payload = [
            'incident_summary' => 'Child was escorted back to classroom safely. Parents notified by phone.',
        ];

        $response = $this->actingAs($staff)->patchJson("/incident-reports/{$emergency->id}/summary", $payload);

        $response->assertOk();
        $response->assertJsonFragment([
            'incident_summary' => 'Child was escorted back to classroom safely. Parents notified by phone.',
        ]);

        $this->assertDatabaseHas('emergencies', [
            'id' => $emergency->id,
            'incident_summary' => 'Child was escorted back to classroom safely. Parents notified by phone.',
        ]);
    }

    public function test_export_pdf_returns_pdf_document(): void
    {
        $staff = $this->createStaffUser();

        $child = ChildProfile::factory()->create(['name' => 'Sophia Ramos']);
        Emergency::factory()->create([
            'child_id' => $child->id,
            'status' => 'resolved',
            'reported_at' => now()->subMinutes(40),
            'resolved_at' => now(),
            'incident_summary' => 'Sensory room deployed. Heart rate normalized within 15 minutes.',
        ]);

        $response = $this->actingAs($staff)->get('/incident-reports/export-pdf');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('incident-report-', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_streams_csv_document(): void
    {
        $staff = $this->createStaffUser();

        $child = ChildProfile::factory()->create(['name' => 'Sophia Ramos']);
        Emergency::factory()->create([
            'child_id' => $child->id,
            'status' => 'resolved',
            'type' => 'medical_crisis',
            'reported_at' => now()->subMinutes(40),
            'resolved_at' => now(),
            'incident_summary' => 'Asthma attack resolved via nebulizer.',
        ]);

        $response = $this->actingAs($staff)->get('/incident-reports/export-csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('incident-report-', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Incident ID', $content);
        $this->assertStringContainsString('Sophia Ramos', $content);
        $this->assertStringContainsString('Asthma attack resolved via nebulizer.', $content);
    }
}
