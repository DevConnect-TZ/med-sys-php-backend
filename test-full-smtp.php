<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Test configuration
echo "=== SMTP Configuration Test ===\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT') . "\n";
echo "MAIL_USERNAME: " . env('MAIL_USERNAME') . "\n";
echo "MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION', 'Not set') . "\n";
echo "MAIL_SCHEME: " . env('MAIL_SCHEME') . "\n";
echo "\n";

// Test mail sending
echo "=== Testing Email Send ===\n";
try {
    \Illuminate\Support\Facades\Mail::raw('SMTP Configuration Test - All systems operational!', function ($message) {
        $message->to('skillflowtz@gmail.com')
                ->subject('Hospital Management System - SMTP Test');
    });
    echo "✓ Email sent successfully to queue\n";
    echo "✓ SMTP Server is working correctly\n";
    echo "\n✓ You can now send invitations through the application!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
