<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       \App\Models\User::factory(10)->create();

         \App\Models\User::factory()->create([
            'name' => 'Jorge Ramirez Penagos',
            'email' => 'jorgepenagos50@gmail.com',
        ]);

            $this->call(UsersTableSeeder::class);
            $this->call(DiplomadoTableSeeder::class);
            $this->call(AlumnosTableSeeder::class);
            $this->call(CuentaDepositoTableSeeder::class);
            $this->call(AlumnoDiplomadoTableSeeder::class);
            $this->call(PagoInscripcionSeeder::class);
            $this->call(PagoAbonoSeeder::class);






    }
}
