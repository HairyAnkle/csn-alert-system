<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_renders_successfully_with_leaflet_map_container(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('leaflet.min.css', false);
        $response->assertSee('leaflet.min.js', false);
        $response->assertSee('id="emergency-map"', false);
        $response->assertSee('Live Emergency Map — Parañaque City');
    }

    public function test_dashboard_supplies_emergencies_with_lat_and_lng_to_view(): void
    {
        $user = User::factory()->create([
            'name' => 'Guardian Test',
            'role' => 'guardian',
        ]);

        $child = ChildProfile::create([
            'name' => 'Joshua Santos',
            'age' => 8,
            'condition' => 'Autism Spectrum Disorder',
            'guardian_id' => $user->id,
            'emergency_contact' => '+63 918 333 4455',
            'medical_notes' => 'Non-verbal and sensitive to loud noises.',
        ]);

        $emergency = Emergency::create([
            'type' => 'Medical Crisis',
            'urgency' => 'high',
            'status' => 'pending',
            'child_id' => $child->id,
            'location' => 'Aguirre Ave., Phase 1, BF Homes, Parañaque City',
            'lat' => 14.4532,
            'lng' => 121.0189,
            'guardian_notes' => 'Child experiencing sensory crisis.',
            'reported_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('emergencies');

        $emergencies = $response->viewData('emergencies');
        $this->assertNotEmpty($emergencies);

        $firstEmergency = $emergencies->first();
        $this->assertEquals($emergency->id, $firstEmergency['id']);
        $this->assertEquals(14.4532, $firstEmergency['lat']);
        $this->assertEquals(121.0189, $firstEmergency['lng']);
        $this->assertEquals('Joshua Santos', $firstEmergency['child']['name']);
        $this->assertEquals('Autism Spectrum Disorder', $firstEmergency['child']['condition']);
    }

    public function test_emergency_auto_geocodes_paranaque_location_when_coordinates_not_provided(): void
    {
        $child = ChildProfile::create([
            'name' => 'Lucas Reyes',
            'age' => 7,
            'condition' => 'Epilepsy',
            'emergency_contact' => '+63 919 444 6611',
        ]);

        // Create without explicit lat/lng
        $emergency = Emergency::create([
            'type' => 'Seizure / Sensory',
            'urgency' => 'high',
            'status' => 'responding',
            'child_id' => $child->id,
            'location' => 'San Antonio Valley 1, Parañaque City',
        ]);

        $this->assertNotNull($emergency->lat);
        $this->assertNotNull($emergency->lng);
        // San Antonio coordinates ~ 14.48, 121.02
        $this->assertGreaterThan(14.40, $emergency->lat);
        $this->assertLessThan(14.55, $emergency->lat);
        $this->assertGreaterThan(120.95, $emergency->lng);
        $this->assertLessThan(121.08, $emergency->lng);
    }

    public function test_dashboard_supplies_monthly_statistics_comparison_for_cards(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('monthlyStats');

        $stats = $response->viewData('monthlyStats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('incidents', $stats);
        $this->assertArrayHasKey('children', $stats);
        $this->assertArrayHasKey('units', $stats);
        $this->assertArrayHasKey('resolved', $stats);

        $this->assertStringContainsString('total incidents from last month', $stats['incidents']['formatted']);
        $this->assertStringContainsString('from last month', $stats['children']['formatted']);
        $this->assertStringContainsString('from last month', $stats['units']['formatted']);
        $this->assertStringContainsString('from last month', $stats['resolved']['formatted']);

        // Assert view HTML contains the monthly metrics in Alpine state
        $response->assertSee('total incidents from last month');
    }
}
