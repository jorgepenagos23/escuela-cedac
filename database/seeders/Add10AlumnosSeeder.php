<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inscripcion;
use App\Models\Diplomado;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class Add10AlumnosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('es_MX');

        // Check if there are diplomados
        $diplomado = Diplomado::first();
        $diplomado_id = $diplomado ? $diplomado->id : 1;
        
        $plan_pagos_opciones = ['contado', 'mensualidades', 'quincenal'];

        for ($i = 0; $i < 10; $i++) {
            $monto_inscripcion = $faker->randomElement([500, 1000, 1500, 2000]);
            
            Inscripcion::create([
                'fecha_inscripcion' => now()->format('Y-m-d'),
                'monto_inscripcion' => $monto_inscripcion,
                'nombre_alumno' => $faker->name,
                'celular' => $faker->numerify('##########'),
                'diplomado_id' => $diplomado_id,
                'correo' => $faker->unique()->safeEmail,
                'curp' => strtoupper($faker->bothify('????######??????##')),
                'estado' => $faker->state,
                'municipio' => $faker->city,
                'direccion_completa' => $faker->address,
                'plan_pagos' => json_encode(['tipo' => $faker->randomElement($plan_pagos_opciones)]),
                'estatus_excel' => 'Pendiente',
                'grupo_campa' => 1,
                'cuentadeposito' => 1,
                // Optional fields
                'nombre_emergencia' => $faker->name,
                'parentesco_emergencia' => 'Familiar',
                'metodo_pago_inscripcion' => 'Transferencia',
                'monto_descuento' => 0
            ]);
        }
    }
}
