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
        'tutor',
        'alumno_id',
        'cuentadeposito	',
        'diplomado_id',

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
                $inscripcion->save();
            }
            
        });
    }
}
