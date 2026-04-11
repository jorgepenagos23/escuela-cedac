<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('es_MX');

        // IDs base preexistentes en la bd segun tu seeder
        $diplomados = [1, 2];
        $asesores = [5, 6, 8, 9, 10]; // IDs de usuarios rol 'asesor'
        $tutores = [3, 4, 7]; // IDs de usuarios rol 'tutoria'
        $cuentas = [1, 2, 3, 4, 5, 6, 7];

        $meses = [];
        for ($i = 0; $i < 6; $i++) {
            $meses[] = now()->subMonths($i)->format('Y-m-d');
        }

        // Generar 50 Alumnos/Inscripciones (Alumnos Activos)
        for ($i = 0; $i < 50; $i++) {
            
            $diplomadoAsignado = $faker->randomElement($diplomados);
            $costoTotalSimulado = ($diplomadoAsignado == 1) ? 10000 : 8000;
            $monto_inscripcion = $faker->randomElement([500, 1000, 1500]);
            
            $fecha_insc = $faker->randomElement($meses);
            
            $grupo = \App\Models\GrupoCampaña::firstOrCreate(
                ['campaña' => 'Campaña Orgánica', 'grupo' => 'A1'],
                ['id_diplomado' => $diplomadoAsignado]
            );

            $inscripcion = \App\Models\Inscripcion::create([
                'fecha_inscripcion' => $fecha_insc,
                'saldo' => $costoTotalSimulado, // El trigger del modelo restará tras el create
                'monto_inscripcion' => $monto_inscripcion,
                'nombre_alumno' => $faker->firstName . ' ' . $faker->lastName . ' ' . $faker->lastName,
                'celular' => $faker->numerify('##########'),
                'adicional' => $faker->email,
                'asesor' => $faker->randomElement($asesores),
                'tutor' => $faker->randomElement($tutores),
                'grupo_campa' => $grupo->id,
                'cuentadeposito' => $faker->randomElement($cuentas),
                'diplomado_id' => $diplomadoAsignado,
                'fecha_primer_pago_colegiatura' =>  \Carbon\Carbon::parse($fecha_insc)->addDays(15)->format('Y-m-d'),
                'created_at' => $fecha_insc,
            ]);

            // Forzar fecha de creacion para simular el pasado
            $inscripcion->created_at = $fecha_insc;
            $inscripcion->save();

            // Agregar aleatoriamente colegiaturas para dejar carteras o liquidar algunos
            // 20% no tiene pagos (morosos duros), 50% tiene 1-3 pagos (a medio camino), 30% está liquidado
            $perfilPago = $faker->numberBetween(1, 10);
            
            $numPagos = 0;
            if ($perfilPago > 2 && $perfilPago <= 7) {
                $numPagos = $faker->numberBetween(1, 3);
            } elseif ($perfilPago > 7) {
                // Simular 4 o 5 pagos cubriendo la mayoria
                $numPagos = $faker->numberBetween(4, 5); 
            }

            for ($j = 0; $j < $numPagos; $j++) {
                try {
                    // Validar si el saldo da, en cada loop el trigger descuenta
                    $inscrip_actual = \App\Models\Inscripcion::find($inscripcion->id);
                    if($inscrip_actual && $inscrip_actual->saldo > 0) {
                        
                        $pagoMonto = $faker->randomElement([1500, 2000]);
                        // Asegurar de no pasarnos del saldo (liquidar)
                        if ($pagoMonto > $inscrip_actual->saldo) {
                            $pagoMonto = $inscrip_actual->saldo;
                        }

                        $fecha_pago = \Carbon\Carbon::parse($fecha_insc)->addMonths($j + 1)->format('Y-m-d');
                        
                        \App\Models\Pagos::create([
                            'Fecha_PrimerContacto' => $fecha_pago,
                            'pago_colegiatura' => $pagoMonto,
                            'status' => 'Pagado',
                            'tutor' => $inscripcion->tutor, // Quien registró el pago
                            'alumno_id' => $inscripcion->id,
                            'cuentadeposito' => $inscripcion->cuentadeposito,
                            'diplomado_id' => $inscripcion->diplomado_id,
                            'created_at' => $fecha_pago,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Si el saldo cae a cero o menos, brinca y continua
                    continue;
                }
            }
        }
    }
}
