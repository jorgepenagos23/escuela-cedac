<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PagoInscripcionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $faker = Faker::create();

        $alumnoID=1;
        foreach(range(1,100) as $index){

        \App\Models\Inscripcion::create([
            'fecha_inscripcion' =>$faker->date,
            'descripcion'=>$faker->paragraph,
            'monto_total'=>7000.00,
            'monto_inscripcion'=>600.00,
            'cuentadeposito'=>$faker->numberBetween(1,4),
            'diplomado_id'=>$faker->numberBetween(1,10),
            'alumno_id'=>$alumnoID++,
        
        ]);

        }

    }
}
