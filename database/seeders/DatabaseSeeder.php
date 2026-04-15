<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoAccounts = [
            [
                'name' => 'Administrator',
                'email' => 'admin@hospital.com',
                'password' => 'AdminPass123!@',
                'role' => 'admin',
                'phone' => '+1234567890',
            ],
            [
                'name' => 'Dr. John Doe',
                'email' => 'doctor@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'doctor',
                'phone' => '+1234567891',
            ],
            [
                'name' => 'Nurse Jane',
                'email' => 'nurse@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'nurse',
                'phone' => '+1234567892',
            ],
            [
                'name' => 'Receptionist Mike',
                'email' => 'receptionist@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'receptionist',
                'phone' => '+1234567893',
            ],
            [
                'name' => 'Cashier Sarah',
                'email' => 'cashier@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'cashier',
                'phone' => '+1234567894',
            ],
            [
                'name' => 'Lab Tech Alex',
                'email' => 'lab@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'lab_technician',
                'phone' => '+1234567895',
            ],
            [
                'name' => 'Pharmacist Emma',
                'email' => 'pharmacist@hospital.com',
                'password' => 'DemoPass123!@',
                'role' => 'pharmacist',
                'phone' => '+1234567896',
            ],
        ];

        foreach ($demoAccounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'phone' => $account['phone'],
                    'is_active' => true,
                ]
            );
        }
    }
}
