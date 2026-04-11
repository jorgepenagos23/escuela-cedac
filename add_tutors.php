<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

for($i = 1; $i <= 3; $i++){
    $user = \App\Models\User::factory()->create([
        'name' => 'Tutor Ejemplo '.$i,
        'email' => 'tutor'.$i.'@cedac.edu.mx'
    ]);
    $user->assignRole('Tutor');
    echo "Created tutor: ".$user->name."\n";
}
