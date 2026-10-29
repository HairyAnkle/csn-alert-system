<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'CSN Superadmin',
                'email' => 'superadmin@csn.gov.ph',
                'password' => Hash::make(config('auth.initial_passwords.superadmin')),
                'role' => 'superadmin',
                'unit' => 'System Developer & Command Administration',
                'phone' => '+63 917 000 0001',
                'status' => 'active',
            ],
            [
                'name' => 'CSN Administrator',
                'email' => 'admin@csn.gov.ph',
                'password' => Hash::make(config('auth.initial_passwords.admin')),
                'role' => 'admin',
                'unit' => 'CSN Center Operations Desk',
                'phone' => '+63 917 111 0001',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. BF Homes Emergency Response Team',
                'email' => 'responder@csn.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8825-4411',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. San Antonio Emergency Response Team',
                'email' => 'brgy.sanantonio@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8820-7377',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. Don Bosco Emergency Response Team',
                'email' => 'brgy.donbosco@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8821-3190',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. San Dionisio Emergency Response Team',
                'email' => 'brgy.sandionisio@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8826-1144',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. San Isidro Emergency Response Team',
                'email' => 'brgy.sanisidro@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8825-6012',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. Baclaran Emergency Response Team',
                'email' => 'brgy.baclaran@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8832-0545',
                'status' => 'active',
            ],
            [
                'name' => 'Brgy. Sun Valley Emergency Response Team',
                'email' => 'brgy.sunvalley@paranaque.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'unit' => 'Parañaque Barangay Emergency Response Unit',
                'phone' => '(02) 8823-1490',
                'status' => 'active',
            ],
            [
                'name' => 'Elena Ramos',
                'email' => 'guardian@csn.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'guardian',
                'phone' => '+63 918 333 4455',
                'status' => 'active',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'guardian.parent@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'guardian',
                'phone' => '+63 917 555 0192',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
