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
                'name' => 'CSN Administrator',
                'email' => 'admin@csn.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+63 917 111 0001',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Angela Gomez',
                'email' => 'staff@csn.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'phone' => '+63 917 222 0002',
                'status' => 'active',
            ],
            [
                'name' => 'Officer Roberto Reyes',
                'email' => 'responder@csn.gov.ph',
                'password' => Hash::make('password'),
                'role' => 'responder',
                'phone' => '+63 917 888 1234',
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
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
