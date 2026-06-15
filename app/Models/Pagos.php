<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    use HasFactory;
    protected $table ='pagos_colegiatura';


    protected $fillable =[
        'id',
        'Fecha_PrimerContacto',
        'pago_colegiatura',
        'status',
        'motivo_cancelacion',
        'tutor',
        'alumno_id',
        'cuentadeposito	',
        'diplomado_id',
        'comprobante_path',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pago) {
            // Obtener la inscripción asociada al pago
            $inscripcion = Inscripcion::where('id', $pago->alumno_id)
                ->where('diplomado_id', $pago->diplomado_id)
                ->first();

            // Verificar si se encontró la inscripción y si su monto total es menor o igual a 0
            if ($inscripcion && $inscripcion->saldo <= 0) {
                // Lanzar una excepción para detener la operación y mostrar un mensaje de error
                throw new \Exception('No se pueden agregar más pagos, el monto total es igual o menor que 0.');
            }

            // Si se encontró la inscripción y su monto total es mayor que 0,
            // restar el monto del abono al monto_total
            if ($inscripcion && $inscripcion->saldo > 0) {
                $inscripcion->saldo -= $pago->pago_colegiatura;
                
                // Distribuir el total pagado (histórico + nuevo abono) en el plan de pagos
                if ($inscripcion->plan_pagos) {
                    $plan = is_string($inscripcion->plan_pagos) ? json_decode($inscripcion->plan_pagos, true) : $inscripcion->plan_pagos;
                    if (is_array($plan)) {
                        // Calcular total pagado activo incluyendo el nuevo abono (que aún no está en BD)
                        $totalPagadoActivo = Pagos::where('alumno_id', $inscripcion->id)
                                                  ->where('status', 'Activo')
                                                  ->sum('pago_colegiatura') + $pago->pago_colegiatura;

                        $montoAbono = (float) $totalPagadoActivo;
                        
                        foreach ($plan as &$cuota) {
                            $cuota['abonado'] = 0;
                            $cuota['estado'] = 'pendiente';

                            if ($montoAbono > 0) {
                                $montoCuota = (float) ($cuota['monto'] ?? 0);
                                if ($montoAbono >= $montoCuota) {
                                    $cuota['abonado'] = $montoCuota;
                                    $cuota['estado'] = 'pagado';
                                    $montoAbono -= $montoCuota;
                                } else {
                                    $cuota['abonado'] = $montoAbono;
                                    $cuota['estado'] = 'parcial';
                                    $montoAbono = 0;
                                }
                            }
                        }

                        $inscripcion->plan_pagos = json_encode($plan);
                    }
                }

                $inscripcion->save();
            }
            
        });
    }

    public function usuarioTutor()
    {
        return $this->belongsTo(User::class, 'tutor', 'id');
    }
}
