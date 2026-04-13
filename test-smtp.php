<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

try {
    // Try to send a test email
    \Illuminate\Support\Facades\Mail::raw('This is a test email from SMTP', function ($message) {
        $message->to('skillflowtz@gmail.com')
                ->subject('SMTP Configuration Test');
    });
    echo "✓ Email sent successfully!\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Class: " . get_class($e) . "\n";
    if (method_exists($e, 'getPrevious') && $e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}
