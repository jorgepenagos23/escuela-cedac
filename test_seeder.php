<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Artisan::call('db:seed', ['--class' => 'DemoDataSeeder']);
    echo "Exito\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
