<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;


    protected $table ='pago_inscripcion';

    protected $fillable =[

    'id',
    'fecha_inscripcion',
    'descripcion',
    'monto_total',
    'monto_inscripcion',
    'cuentadeposito',
    'diplomado_id',
    'alumno_id',

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($inscripcion) {
            $diplomado = Diplomado::find($inscripcion->diplomado_id);
            $precio_diplomado = $diplomado->costo_total;

            // Establecer el monto total igual al precio del diplomado
            $inscripcion->monto_total = $precio_diplomado;

            $comision=2;
            // Restar el monto de la inscripción al monto total
            $inscripcion->monto_total -= $inscripcion->monto_inscripcion;
        });
    }

}
