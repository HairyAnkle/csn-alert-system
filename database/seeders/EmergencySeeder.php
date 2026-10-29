<?php

namespace Database\Seeders;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmergencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates realistic historical resolved incident reports across Parañaque City.
     * Leaves active emergency queue at 0 (Standby Mode) for live testing.
     */
    public function run(): void
    {
        $juan = ChildProfile::where('name', 'Juan dela Cruz')->first();
        $sofia = ChildProfile::where('name', 'Sofia Santos')->first();
        $gabriel = ChildProfile::where('name', 'Gabriel Mendoza')->first();
        $mateo = ChildProfile::where('name', 'Mateo Reyes')->first();
        $bea = ChildProfile::where('name', 'Bea Dizon')->first();

        $bfTeam = User::where('email', 'responder@csn.gov.ph')->first();
        $sanAntonioTeam = User::where('email', 'brgy.sanantonio@paranaque.gov.ph')->first();
        $donBoscoTeam = User::where('email', 'brgy.donbosco@paranaque.gov.ph')->first();
        $sanDionisioTeam = User::where('email', 'brgy.sandionisio@paranaque.gov.ph')->first();
        $sunValleyTeam = User::where('email', 'brgy.sunvalley@paranaque.gov.ph')->first();

        $historical = [
            // --- 2 Months Ago ---
            [
                'child_id' => $juan?->id,
                'guardian_id' => $juan?->guardian_id,
                'type' => 'seizure_sensory',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $bfTeam?->id,
                'location' => 'Aguirre Ave, BF Homes, Parañaque City',
                'lat' => 14.4510,
                'lng' => 121.0210,
                'guardian_notes' => 'Loud construction noise triggered sensory distress.',
                'incident_summary' => 'Responders arrived in 4 minutes, escorted child to quiet zone, no sirens used. Situation de-escalated safely.',
                'reported_at' => Carbon::now()->subMonths(2)->day(10)->setTime(14, 10),
                'acknowledged_at' => Carbon::now()->subMonths(2)->day(10)->setTime(14, 11),
                'dispatched_at' => Carbon::now()->subMonths(2)->day(10)->setTime(14, 14),
                'resolved_at' => Carbon::now()->subMonths(2)->day(10)->setTime(14, 38),
            ],
            [
                'child_id' => $mateo?->id,
                'guardian_id' => $mateo?->guardian_id,
                'type' => 'medical_crisis',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $sunValleyTeam?->id,
                'location' => 'Sun Valley Drive, Parañaque City',
                'lat' => 14.4921,
                'lng' => 121.0385,
                'guardian_notes' => 'Brief absence seizure episode.',
                'incident_summary' => 'Paramedic checked vitals, pulse and oxygen normal. Hydration given by guardian.',
                'reported_at' => Carbon::now()->subMonths(2)->day(22)->setTime(9, 30),
                'acknowledged_at' => Carbon::now()->subMonths(2)->day(22)->setTime(9, 31),
                'dispatched_at' => Carbon::now()->subMonths(2)->day(22)->setTime(9, 34),
                'resolved_at' => Carbon::now()->subMonths(2)->day(22)->setTime(9, 58),
            ],

            // --- Last Month ---
            [
                'child_id' => $bea?->id,
                'guardian_id' => $bea?->guardian_id,
                'type' => 'wandering',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $sanDionisioTeam?->id,
                'location' => 'San Dionisio, Parañaque City',
                'lat' => 14.4984,
                'lng' => 120.9882,
                'guardian_notes' => 'Child wandered out of community park.',
                'incident_summary' => 'Mobile responder located child near barangay hall in 5 minutes. Reunited with guardian safely.',
                'reported_at' => Carbon::now()->subMonth()->day(5)->setTime(11, 15),
                'acknowledged_at' => Carbon::now()->subMonth()->day(5)->setTime(11, 16),
                'dispatched_at' => Carbon::now()->subMonth()->day(5)->setTime(11, 19),
                'resolved_at' => Carbon::now()->subMonth()->day(5)->setTime(11, 40),
            ],
            [
                'child_id' => $sofia?->id,
                'guardian_id' => $sofia?->guardian_id,
                'type' => 'medical_crisis',
                'urgency' => 'low',
                'status' => 'resolved',
                'responder_id' => $sanAntonioTeam?->id,
                'location' => 'San Antonio Valley 1, Parañaque City',
                'lat' => 14.4722,
                'lng' => 121.0185,
                'guardian_notes' => 'Fever and general fatigue distress.',
                'incident_summary' => 'Barangay health personnel administered temperature check and provided oral rehydration.',
                'reported_at' => Carbon::now()->subMonth()->day(14)->setTime(15, 20),
                'acknowledged_at' => Carbon::now()->subMonth()->day(14)->setTime(15, 21),
                'dispatched_at' => Carbon::now()->subMonth()->day(14)->setTime(15, 25),
                'resolved_at' => Carbon::now()->subMonth()->day(14)->setTime(15, 50),
            ],
            [
                'child_id' => $gabriel?->id,
                'guardian_id' => $gabriel?->guardian_id,
                'type' => 'seizure_sensory',
                'urgency' => 'low',
                'status' => 'resolved',
                'responder_id' => $donBoscoTeam?->id,
                'location' => 'Dona Soledad Ave, Better Living, Parañaque City',
                'lat' => 14.4855,
                'lng' => 121.0345,
                'guardian_notes' => 'Wheelchair ramp malfunction during rain.',
                'incident_summary' => 'Responders assisted wheelchair transfer and provided shelter until transport arrived.',
                'reported_at' => Carbon::now()->subMonth()->day(27)->setTime(18, 0),
                'acknowledged_at' => Carbon::now()->subMonth()->day(27)->setTime(18, 1),
                'dispatched_at' => Carbon::now()->subMonth()->day(27)->setTime(18, 4),
                'resolved_at' => Carbon::now()->subMonth()->day(27)->setTime(18, 30),
            ],

            // --- This Month (Past Days - Leaves 0 Active Today) ---
            [
                'child_id' => $juan?->id,
                'guardian_id' => $juan?->guardian_id,
                'type' => 'seizure_sensory',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $bfTeam?->id,
                'location' => 'SM City BF Homes, Dr. A. Santos Ave, Parañaque City',
                'lat' => 14.4532,
                'lng' => 121.0264,
                'guardian_notes' => 'Child experienced intense auditory sensory overload near the main entrance.',
                'incident_summary' => 'Brgy. BF Homes Emergency Response Team arrived on-site in 4 minutes. Team followed CSN care guide: no sirens, dimmed perimeter flashlights, and escorted guardian and child to the mall quiet room. Vitals stable; safe release to parent.',
                'reported_at' => Carbon::now()->subDays(6)->setTime(14, 20),
                'acknowledged_at' => Carbon::now()->subDays(6)->setTime(14, 21),
                'dispatched_at' => Carbon::now()->subDays(6)->setTime(14, 24),
                'resolved_at' => Carbon::now()->subDays(6)->setTime(14, 45),
            ],
            [
                'child_id' => $bea?->id,
                'guardian_id' => $bea?->guardian_id,
                'type' => 'wandering',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $sanDionisioTeam?->id,
                'location' => 'Kabihasnan St., San Dionisio, Parañaque City',
                'lat' => 14.4984,
                'lng' => 120.9882,
                'guardian_notes' => 'Child bolted out of residence gate while guardian received package. Fast runner.',
                'incident_summary' => 'Broadcast dispatched to Brgy. San Dionisio mobile patrol. Child safely spotted within 300 meters near barangay outpost. Responders engaged child calmly with bubble toy distraction. Reunited with guardian without incident.',
                'reported_at' => Carbon::now()->subDays(4)->setTime(10, 15),
                'acknowledged_at' => Carbon::now()->subDays(4)->setTime(10, 16),
                'dispatched_at' => Carbon::now()->subDays(4)->setTime(10, 18),
                'resolved_at' => Carbon::now()->subDays(4)->setTime(10, 38),
            ],
            [
                'child_id' => $mateo?->id,
                'guardian_id' => $mateo?->guardian_id,
                'type' => 'medical_crisis',
                'urgency' => 'high',
                'status' => 'resolved',
                'responder_id' => $sunValleyTeam?->id,
                'location' => 'Sun Valley Drive, Parañaque City',
                'lat' => 14.4921,
                'lng' => 121.0385,
                'guardian_notes' => 'Child exhibited short 25-second absence seizure in living area followed by disorientation.',
                'incident_summary' => 'Brgy. Sun Valley Emergency Response Team dispatched with paramedic. Child assessed in recovery position; airway clear, blood oxygen 99%, heart rate 86 bpm. Guardian administered prescribed hydration. No hospital transfer required.',
                'reported_at' => Carbon::now()->subDays(3)->setTime(16, 40),
                'acknowledged_at' => Carbon::now()->subDays(3)->setTime(16, 41),
                'dispatched_at' => Carbon::now()->subDays(3)->setTime(16, 44),
                'resolved_at' => Carbon::now()->subDays(3)->setTime(17, 10),
            ],
            [
                'child_id' => $sofia?->id,
                'guardian_id' => $sofia?->guardian_id,
                'type' => 'medical_crisis',
                'urgency' => 'low',
                'status' => 'resolved',
                'responder_id' => $sanAntonioTeam?->id,
                'location' => 'San Antonio Valley 1 Park, Parañaque City',
                'lat' => 14.4722,
                'lng' => 121.0185,
                'guardian_notes' => 'Minor scrape and distress on community park swing. Guardian requested first aid check.',
                'incident_summary' => 'San Antonio Barangay responders provided gentle antiseptic cleansing and adhesive bandage. Child comforted and cleared in good spirits.',
                'reported_at' => Carbon::now()->subDays(2)->setTime(11, 5),
                'acknowledged_at' => Carbon::now()->subDays(2)->setTime(11, 6),
                'dispatched_at' => Carbon::now()->subDays(2)->setTime(11, 9),
                'resolved_at' => Carbon::now()->subDays(2)->setTime(11, 25),
            ],
            [
                'child_id' => $gabriel?->id,
                'guardian_id' => $gabriel?->guardian_id,
                'type' => 'seizure_sensory',
                'urgency' => 'low',
                'status' => 'resolved',
                'responder_id' => $donBoscoTeam?->id,
                'location' => 'Dona Soledad Ave, Better Living, Parañaque City',
                'lat' => 14.4855,
                'lng' => 121.0345,
                'guardian_notes' => 'Sudden thunderstorm lightning and heavy thunder caused spastic muscle cramping and agitation in wheelchair.',
                'incident_summary' => 'Brgy. Don Bosco responders assisted guardian with wheelchair ramp maneuver into covered pavilion. Reassured child and assisted transport home safely.',
                'reported_at' => Carbon::now()->subDay()->setTime(17, 30),
                'acknowledged_at' => Carbon::now()->subDay()->setTime(17, 31),
                'dispatched_at' => Carbon::now()->subDay()->setTime(17, 34),
                'resolved_at' => Carbon::now()->subDay()->setTime(18, 5),
            ],
        ];

        foreach ($historical as $inc) {
            if (! empty($inc['child_id'])) {
                Emergency::firstOrCreate(
                    [
                        'child_id' => $inc['child_id'],
                        'reported_at' => $inc['reported_at'],
                    ],
                    $inc
                );
            }
        }
    }
}
