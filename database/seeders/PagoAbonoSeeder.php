<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PagoAbonoSeeder extends Seeder
{
const Descuento='Jorge';

    public function run(): void
    {
        $faker = Faker::create();
        $alumnoID=1;
        foreach(range(1,80) as $index){
        \App\Models\Pagos::create([
        'descripcion'=>$faker->paragraph,
        'fecha_abono'=>now(),
        'monto_abono'=>1600.00,
        'porcentaje_aplicado'=>0,
        'con_descuento' => $faker->boolean,
        'aprobado_descuento' => $faker->boolean,
        'alumno_id'=>$alumnoID++,
        'cuentadeposito'=>$faker->numberBetween(1,4),
        'diplomado_id'=>$faker->numberBetween(1,10),

        ]);
            

        }


    }
}
