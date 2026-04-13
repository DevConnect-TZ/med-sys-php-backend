<?php

// Script to create admin user
// Run: php create_admin.php

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n=== Hospital Management System - Create Admin User ===\n\n";

// Check if admin already exists
$adminExists = User::where('role', 'admin')->first();
if ($adminExists) {
    echo "✓ Admin user already exists: " . $adminExists->email . "\n\n";
    exit;
}

// Create new admin user
try {
    $admin = User::create([
        'name' => 'Administrator',
        'email' => 'admin@hospital.com',
        'password' => Hash::make('AdminPass123!@'),
        'role' => 'admin',
        'phone' => '+1234567890',
        'specialization' => 'System Administration',
        'license_number' => 'ADMIN-001',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    echo "✓ Admin user created successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Email: " . $admin->email . "\n";
    echo "Password: AdminPass123!@\n";
    echo "Role: " . $admin->role . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "✓ Use these credentials to login:\n";
    echo "   URL: http://localhost:8000/\n";
    echo "   Login Endpoint: POST /api/auth/login\n\n";
    
    echo "   cURL Command:\n";
    echo '   curl -X POST http://localhost:8000/api/auth/login \\' . "\n";
    echo '     -H "Content-Type: application/json" \\' . "\n";
    echo '     -d \'{"email":"admin@hospital.com","password":"AdminPass123!@"}\'' . "\n\n";

} catch (\Exception $e) {
    echo "✗ Error creating admin user: " . $e->getMessage() . "\n\n";
    exit(1);
}
