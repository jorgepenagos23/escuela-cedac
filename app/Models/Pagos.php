<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    use HasFactory;
    protected $table ='pago_abono';


    protected $fillable =[
        'id',
        'descripcion',
        'fecha_abono',
        'monto_abono',
        'porcentaje_aplicado',
        'con_descuento',
        'aprobado_descuento',
        'cuentadeposito',
        'alumno_id',
        'diplomado_id'


    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pago) {
            // Obtener la inscripción asociada al pago
            $inscripcion = Inscripcion::where('alumno_id', $pago->alumno_id)
                ->where('diplomado_id', $pago->diplomado_id)
                ->first();

            // Verificar si se encontró la inscripción y si su monto total es menor o igual a 0
            if ($inscripcion && $inscripcion->monto_total <= 0) {
                // Lanzar una excepción para detener la operación y mostrar un mensaje de error
                throw new \Exception('No se pueden agregar más pagos, el monto total es igual o menor que 0.');
            }

            // Si se encontró la inscripción y su monto total es mayor que 0,
            // restar el monto del abono al monto_total
            if ($inscripcion && $inscripcion->monto_total > 0) {
                $inscripcion->monto_total -= $pago->monto_abono;
                $inscripcion->save();
            }
        });
    }
}
