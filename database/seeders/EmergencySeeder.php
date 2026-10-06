<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmergencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // 1. Seed Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@paranaque.gov.ph'],
            [
                'name' => 'Dr. Angela Gomez',
                'role' => 'staff',
                'phone' => '+63 917 888 1234',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $responder = User::firstOrCreate(
            ['email' => 'pdrrmo.cruz@paranaque.gov.ph'],
            [
                'name' => 'Capt. Roberto Cruz',
                'role' => 'responder',
                'phone' => '+63 920 555 9876',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $guardianJoshua = User::firstOrCreate(
            ['email' => 'teresa.santos@gmail.com'],
            [
                'name' => 'Teresa Santos',
                'role' => 'guardian',
                'phone' => '+63 918 333 4455',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $guardianMaria = User::firstOrCreate(
            ['email' => 'elena.delacruz@gmail.com'],
            [
                'name' => 'Elena Dela Cruz',
                'role' => 'guardian',
                'phone' => '+63 917 222 7788',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $guardianLucas = User::firstOrCreate(
            ['email' => 'marco.reyes@gmail.com'],
            [
                'name' => 'Marco Reyes',
                'role' => 'guardian',
                'phone' => '+63 919 444 6611',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        $guardianBea = User::firstOrCreate(
            ['email' => 'carmen.gonzales@gmail.com'],
            [
                'name' => 'Carmen Gonzales',
                'role' => 'guardian',
                'phone' => '+63 922 111 8899',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Seed Child Profiles
        $child1 = ChildProfile::updateOrCreate(
            ['name' => 'Joshua Santos'],
            [
                'age' => 8,
                'condition' => 'Autism Spectrum Disorder (Non-verbal)',
                'guardian_id' => $guardianJoshua->id,
                'guardian_name' => 'Teresa Santos',
                'emergency_contact' => '+63 918 333 4455',
                'medical_notes' => 'Non-verbal, sensitive to loud sirens and flashing strobe lights. Responds positively to visual communication cards.',
                'created_by' => $admin->id,
            ]
        );

        $child2 = ChildProfile::updateOrCreate(
            ['name' => 'Maria Clara Dela Cruz'],
            [
                'age' => 10,
                'condition' => 'Cerebral Palsy (Wheelchair-reliant)',
                'guardian_id' => $guardianMaria->id,
                'guardian_name' => 'Elena Dela Cruz',
                'emergency_contact' => '+63 917 222 7788',
                'medical_notes' => 'Requires wheelchair ramp assistance and transfer protocol. Medication for muscle spasms kept in backpack.',
                'created_by' => $admin->id,
            ]
        );

        $child3 = ChildProfile::updateOrCreate(
            ['name' => 'Lucas Reyes'],
            [
                'age' => 7,
                'condition' => 'Epilepsy & Sensory Processing Disorder',
                'guardian_id' => $guardianLucas->id,
                'guardian_name' => 'Marco Reyes',
                'emergency_contact' => '+63 919 444 6611',
                'medical_notes' => 'Prone to absence and tonic-clonic seizures under extreme heat or flickering light. Emergency Diazepam prescribed.',
                'created_by' => $admin->id,
            ]
        );

        $child4 = ChildProfile::updateOrCreate(
            ['name' => 'Bea Gonzales'],
            [
                'age' => 12,
                'condition' => 'Down Syndrome (Cardiac Precaution)',
                'guardian_id' => $guardianBea->id,
                'guardian_name' => 'Carmen Gonzales',
                'emergency_contact' => '+63 922 111 8899',
                'medical_notes' => 'Repaired VSD in infancy; monitor pulse oximetry during high distress. Calm, gentle tone advised.',
                'created_by' => $admin->id,
            ]
        );

        $child5 = ChildProfile::updateOrCreate(
            ['name' => 'Miguel Tan'],
            [
                'age' => 9,
                'condition' => 'ADHD & Wandering Tendency',
                'guardian_id' => null,
                'guardian_name' => 'Grace Tan',
                'emergency_contact' => '+63 928 777 3322',
                'medical_notes' => 'Wandering risk when overstimulated. Wears silicone CSN QR wristband. Loves music and familiar melodies.',
                'created_by' => $admin->id,
            ]
        );

        $child6 = ChildProfile::updateOrCreate(
            ['name' => 'Althea Mendoza'],
            [
                'age' => 11,
                'condition' => 'Severe Asthma & Sensory Sensitivities',
                'guardian_id' => null,
                'guardian_name' => 'Ricardo Mendoza',
                'emergency_contact' => '+63 916 444 8822',
                'medical_notes' => 'Carry inhaler (Salbutamol) at all times. Allergic to sudden temperature shifts and dust.',
                'created_by' => $admin->id,
            ]
        );

        // 3. Seed Emergencies with precise Parañaque City GPS Coordinates
        // Clear existing emergencies to avoid duplicate accumulation on re-seed
        Emergency::truncate();

        Emergency::create([
            'type' => 'Medical Crisis',
            'urgency' => 'high',
            'status' => 'pending',
            'child_id' => $child1->id,
            'location' => 'Aguirre Ave., Phase 1, BF Homes, Parañaque City',
            'lat' => 14.4532,
            'lng' => 121.0189,
            'guardian_notes' => 'Child is hyperventilating in high anxiety. Non-verbal; please approach softly without sirens.',
            'reported_at' => now()->subMinutes(5),
        ]);

        Emergency::create([
            'type' => 'Seizure / Sensory',
            'urgency' => 'high',
            'status' => 'responding',
            'child_id' => $child3->id,
            'location' => 'San Antonio Valley 1, Parañaque City',
            'lat' => 14.4826,
            'lng' => 121.0264,
            'guardian_notes' => 'Seizure started 6 mins ago. Responder Unit 4 is 3 minutes away.',
            'reported_at' => now()->subMinutes(14),
            'acknowledged_at' => now()->subMinutes(12),
            'dispatched_at' => now()->subMinutes(8),
        ]);

        Emergency::create([
            'type' => 'Wandering Alert',
            'urgency' => 'low',
            'status' => 'responding',
            'child_id' => $child5->id,
            'location' => 'Moonwalk Village Phase 2, Parañaque City',
            'lat' => 14.4981,
            'lng' => 121.0125,
            'guardian_notes' => 'Child spotted near community clubhouse wearing yellow t-shirt. Barangay tanod en route to verify.',
            'reported_at' => now()->subMinutes(28),
            'acknowledged_at' => now()->subMinutes(25),
            'dispatched_at' => now()->subMinutes(20),
        ]);

        Emergency::create([
            'type' => 'Medical Crisis',
            'urgency' => 'high',
            'status' => 'resolved',
            'child_id' => $child2->id,
            'location' => 'Better Living Subd., Don Bosco, Parañaque City',
            'lat' => 14.4889,
            'lng' => 121.0381,
            'guardian_notes' => 'Wheelchair ramp minor slip. PDRRMO emergency medical team treated minor abrasion on site.',
            'reported_at' => now()->subHours(3),
            'acknowledged_at' => now()->subHours(3)->addMinutes(2),
            'dispatched_at' => now()->subHours(3)->addMinutes(5),
            'resolved_at' => now()->subHours(2),
        ]);

        Emergency::create([
            'type' => 'Sensory Overload',
            'urgency' => 'low',
            'status' => 'resolved',
            'child_id' => $child4->id,
            'location' => 'Redemptorist Rd., Baclaran, Parañaque City',
            'lat' => 14.5312,
            'lng' => 120.9934,
            'guardian_notes' => 'Agitated due to intense street loudspeaker noise. Mobile calming tent provided by CSN staff.',
            'reported_at' => now()->subHours(5),
            'acknowledged_at' => now()->subHours(5)->addMinutes(3),
            'dispatched_at' => now()->subHours(5)->addMinutes(6),
            'resolved_at' => now()->subHours(4),
        ]);
    }
}
