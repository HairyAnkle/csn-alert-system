<?php

namespace Database\Seeders;

use App\Models\ChildProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ChildProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $elena = User::where('email', 'guardian@csn.gov.ph')->first();
        $maria = User::where('email', 'guardian.parent@gmail.com')->first();

        $children = [
            [
                'name' => 'Juan dela Cruz',
                'age' => 9,
                'birthdate' => Carbon::now()->subYears(9)->format('Y-m-d'),
                'condition' => 'Autism Spectrum Disorder',
                'address' => 'Blk 12 Lot 4, San Antonio Valley 1, Parañaque City',
                'guardian_id' => $elena?->id,
                'guardian_name' => 'Elena Ramos',
                'guardian_phone' => '+63 918 333 4455',
                'emergency_contact' => '+63 918 333 4455',
                'emergency_contact_name' => 'Elena Ramos (Mother)',
                'emergency_contact_phone' => '+63 918 333 4455',
                'allergies' => 'None reported',
                'medications' => 'Melatonin 2mg at bedtime',
                'communication_notes' => 'Non-verbal with PECS cards. Uses tablet for communication. Sensitive to sirens and loud shouting.',
                'medical_notes' => 'Prone to severe auditory sensory overload in crowded spaces. Keep lights dimmed and approach without sudden loud sirens.',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Sofia Santos',
                'age' => 7,
                'birthdate' => Carbon::now()->subYears(7)->format('Y-m-d'),
                'condition' => 'Down Syndrome',
                'address' => 'Phase 2, BF Homes, Parañaque City',
                'guardian_id' => $maria?->id,
                'guardian_name' => 'Maria Santos',
                'guardian_phone' => '+63 917 555 0192',
                'emergency_contact' => '+63 917 555 0192',
                'emergency_contact_name' => 'Maria Santos (Mother)',
                'emergency_contact_phone' => '+63 917 555 0192',
                'allergies' => 'Penicillin',
                'medications' => 'Levothyroxine 25mcg daily',
                'communication_notes' => 'Verbal with short sentences. Responds warmly to calm, reassuring tone and visual hand gestures.',
                'medical_notes' => 'Congenital heart defect (repaired). Avoid strenuous physical restraint; maintain calm seating posture.',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Gabriel Mendoza',
                'age' => 11,
                'birthdate' => Carbon::now()->subYears(11)->format('Y-m-d'),
                'condition' => 'Cerebral Palsy (Spastic Diplegia)',
                'address' => 'Severina Diamond Subd., Marcelo Green, Parañaque City',
                'guardian_id' => $elena?->id,
                'guardian_name' => 'Elena Ramos',
                'guardian_phone' => '+63 918 333 4455',
                'emergency_contact' => '+63 917 888 2233',
                'emergency_contact_name' => 'Roberto Mendoza (Father)',
                'emergency_contact_phone' => '+63 917 888 2233',
                'allergies' => 'Latex',
                'medications' => 'Baclofen 10mg twice daily',
                'communication_notes' => 'Verbal and expressive. Uses wheelchair and ankle-foot orthoses (AFOs).',
                'medical_notes' => 'Requires wheelchair assistance. Muscle tightness increases under stress; assist gently with mobility transfer.',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Mateo Reyes',
                'age' => 6,
                'birthdate' => Carbon::now()->subYears(6)->format('Y-m-d'),
                'condition' => 'Global Developmental Delay & Epilepsy',
                'address' => 'Sampaguita St., Sun Valley, Parañaque City',
                'guardian_id' => $maria?->id,
                'guardian_name' => 'Maria Santos',
                'guardian_phone' => '+63 917 555 0192',
                'emergency_contact' => '+63 917 555 0192',
                'emergency_contact_name' => 'Maria Santos',
                'emergency_contact_phone' => '+63 917 555 0192',
                'allergies' => 'None',
                'medications' => 'Levetiracetam (Keppra) 250mg syrup twice daily',
                'communication_notes' => 'Limited verbal vocabulary. Pointing and receptive gestures.',
                'medical_notes' => 'History of absence seizures lasting 15-30 seconds. Place in safe lateral recovery position if seizure occurs.',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Bea Dizon',
                'age' => 8,
                'birthdate' => Carbon::now()->subYears(8)->format('Y-m-d'),
                'condition' => 'ADHD (Hyperactive-Impulsive)',
                'address' => 'Dr. A. Santos Ave, San Dionisio, Parañaque City',
                'guardian_id' => $elena?->id,
                'guardian_name' => 'Elena Ramos',
                'guardian_phone' => '+63 918 333 4455',
                'emergency_contact' => '+63 918 333 4455',
                'emergency_contact_name' => 'Elena Ramos',
                'emergency_contact_phone' => '+63 918 333 4455',
                'allergies' => 'Peanuts',
                'medications' => 'Methylphenidate 10mg morning',
                'communication_notes' => 'Fully verbal. Fast speech pace. Tends to bolt towards open gates or roads when excited or panicked.',
                'medical_notes' => 'High elopement/wandering risk. Approach calmly with a favorite toy or soothing distraction.',
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($children as $cData) {
            ChildProfile::updateOrCreate(
                ['name' => $cData['name']],
                $cData
            );
        }
    }
}
