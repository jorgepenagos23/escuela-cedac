<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;


    protected $table ='alumno_inscripcion';

    protected $fillable =[

    'id',
    'fecha_inscripcion',
    'saldo',
    'monto_inscripcion',
    'nombre_alumno',
    'celular',
    'adicional',
    'asesor',
    'tutor',
    'grupo_campa',
    'cuentadeposito',
    'diplomado_id',
    'fecha_primer_pago_colegiatura',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($inscripcion) {
            $diplomado = Diplomado::find($inscripcion->diplomado_id);
            $precio_diplomado = $diplomado->costo_total;

            // Establecer el monto total igual al precio del diplomado
            $inscripcion->saldo = $precio_diplomado;

            $comision=2;
            // Restar el monto de la inscripción al monto total
            $inscripcion->saldo -= $inscripcion->monto_inscripcion;
        });
    }

}
