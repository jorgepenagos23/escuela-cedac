<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inscripcion;
use App\Models\Pagos;
use App\Models\User;
use App\Models\Diplomado;
use App\Models\CuentadeDeposito;
use App\Models\GrupoCampaña;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AlumnosReportesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        // Obtener datos existentes en la BD
        $users = User::pluck('id')->toArray();
        $diplomados = Diplomado::pluck('id')->toArray();
        $cuentas = CuentadeDeposito::pluck('id')->toArray();
        $grupos = GrupoCampaña::pluck('id')->toArray();

        if (empty($users) || empty($diplomados) || empty($cuentas)) {
            $this->command->error('Se requieren usuarios, diplomados y cuentas de depósito previos para ejecutar este seeder. Corre php artisan db:seed primero.');
            return;
        }

        $metodos_pago = ['Efectivo', 'Transferencia Bancaria', 'Depósito en OXXO', 'Tarjeta de Crédito/Débito', 'Paypal'];
        $estados_pago = ['Aprobado', 'Aprobado', 'Aprobado', 'Cancelado', 'Pendiente']; // Más peso para aprobados

        $cantidad_alumnos = 50;
        
        $this->command->info("Generando $cantidad_alumnos alumnos con sus pagos y abonos...");

        for ($i = 0; $i < $cantidad_alumnos; $i++) {
            
            $asesor_id = $faker->randomElement($users);
            $tutor_id = $faker->randomElement($users);
            $diplomado_id = $faker->randomElement($diplomados);
            $monto_inscripcion = $faker->randomElement([500, 1000, 1500, 0]);

            $fecha_inscripcion = Carbon::now()->subDays(rand(10, 150));

            // Desactivamos temporalmente eventos si interfieren con la creación masiva simulada, 
            // pero Inscripcion usa el boot logic de restarle al diplomado.
            // Para asegurar la consistencia permitimos que actúen los listeners de Eloquent.
            $inscripcion = Inscripcion::create([
                'fecha_inscripcion' => $fecha_inscripcion->format('Y-m-d'),
                // 'saldo' es calculado en el Model boot
                'monto_inscripcion' => $monto_inscripcion,
                'nombre_alumno' => $faker->name,
                'celular' => $faker->numerify('##########'),
                'adicional' => $faker->numerify('##########'),
                'asesor' => $asesor_id,
                'tutor' => $tutor_id,
                'grupo_campa' => empty($grupos) ? null : $faker->randomElement($grupos),
                'cuentadeposito' => $faker->randomElement($cuentas),
                'diplomado_id' => $diplomado_id,
                'fecha_primer_pago_colegiatura' => (clone $fecha_inscripcion)->addDays(30)->format('Y-m-d'),
                'correo' => $faker->unique()->safeEmail,
                'curp' => strtoupper($faker->bothify('????######??????##')),
                'nombre_emergencia' => $faker->name,
                'parentesco_emergencia' => $faker->randomElement(['Madre', 'Padre', 'Hermano/a', 'Tutor']),
                'estado' => 'Jalisco', // O mockear aleatorio
                'municipio' => 'Guadalajara',
                'direccion_completa' => $faker->address,
                'metodo_pago_inscripcion' => $faker->randomElement($metodos_pago),
            ]);

            // Generar pagos
            $num_pagos = rand(1, 6);
            for ($p = 0; $p < $num_pagos; $p++) {
                
                $status_pago = $faker->randomElement($estados_pago);
                $pago_colegiatura = $faker->randomElement([500, 800, 1000, 1200, 1500]);
                
                // Si el saldo es menor al pago, no intentamos pagar más
                $inscripcion->refresh();
                if ($inscripcion->saldo <= 0) {
                    break;
                }
                if ($pago_colegiatura > $inscripcion->saldo) {
                    $pago_colegiatura = $inscripcion->saldo;
                }

                    $fecha_pago = clone $fecha_inscripcion;
                    $fecha_pago = $fecha_pago->addDays(($p + 1) * 30);

                try {
                    // Cuidado: Pagos tiene un evento boot que resta el saldo SI el estado es válido o independientemente.
                    // Generar un pago reduce el saldo de la inscripción.
                    Pagos::create([
                        'Fecha_PrimerContacto' => $fecha_pago->format('Y-m-d H:i:s'),
                        'pago_colegiatura' => $pago_colegiatura,
                        'status' => $status_pago,
                        'motivo_cancelacion' => $status_pago === 'Cancelado' ? $faker->sentence() : null,
                        'tutor' => $tutor_id,
                        'alumno_id' => $inscripcion->id,
                        'cuentadeposito' => $faker->randomElement($cuentas),
                        'diplomado_id' => $diplomado_id,
                        'comprobante_path' => null,
                    ]);
                    
                    // Si el seeder de cancelado requiere devolver saldo, 
                    // ajustamos el saldo manualmente ya que según el Pagos::boot lo baja en automático
                    if ($status_pago === 'Cancelado') {
                        $inscripcion->saldo += $pago_colegiatura;
                        $inscripcion->save();
                    }

                } catch (\Exception $e) {
                    // Si lanza error por saldo menor a 0, simplemente terminamos los pagos para este alumno
                    break;
                }
            }
        }
        
        $this->command->info('Seeder de Alumnos y Pagos ejecutado correctamente.');
    }
}
