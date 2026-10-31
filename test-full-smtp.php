<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Run from the backend root: php test-full-smtp.php recipient@example.com
require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$status = Artisan::call('mail:test', [
    'recipient' => $argv[1] ?? '',
]);
echo Artisan::output();
exit($status);
