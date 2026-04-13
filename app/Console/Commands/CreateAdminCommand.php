<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--name=Administrator} {--email=admin@hospital.com} {--password=AdminPass123!@}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user for the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        try {
            // Check if user already exists using raw query
            $exists = DB::table('users')->where('email', $email)->first();
            if ($exists) {
                $this->error("User with email {$email} already exists!");
                return 1;
            }

            // Insert user directly with correct column names
            DB::table('users')->insert([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'phone' => '+1234567890',
                'specialization' => 'System Administration',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('✓ Admin user created successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Email', $email],
                    ['Role', 'admin'],
                    ['Status', 'Active'],
                ]
            );
            $this->newLine();
            $this->info('Login Credentials:');
            $this->line("Email: {$email}");
            $this->line("Password: {$password}");
            $this->newLine();

            return 0;
        } catch (\Exception $e) {
            $this->error('Error creating admin user: ' . $e->getMessage());
            return 1;
        }
    }
}
