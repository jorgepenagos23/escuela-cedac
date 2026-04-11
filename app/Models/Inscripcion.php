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
    'correo',
    'curp',
    'nombre_emergencia',
    'parentesco_emergencia',
    'estado',
    'municipio',
    'direccion_completa',
    'metodo_pago_inscripcion',
    'plan_pagos',
    'descuento_id',
    'monto_descuento',
    'observacion_tutorias',
    'observacion_admisiones',
    'estatus_excel',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($inscripcion) {
            $diplomado = Diplomado::find($inscripcion->diplomado_id);
            if (!$diplomado) return;
            
            $precio_diplomado = (float) $diplomado->costo_total;

            // 1. Iniciamos con el costo base
            $saldo = $precio_diplomado;

            // 2. Aplicamos descuento si existe por ID
            if ($inscripcion->descuento_id) {
                $descuento = Descuento::find($inscripcion->descuento_id);
                if ($descuento && $descuento->vigente) {
                    $monto_calc = $descuento->ahorro($precio_diplomado);
                    $inscripcion->monto_descuento = $monto_calc;
                    $saldo -= $monto_calc;
                }
            } else {
                // 2b. Aplicamos descuento directo si existe
                $saldo -= (float) ($inscripcion->monto_descuento ?? 0);
            }

            // 3. Restamos el abono de inscripción
            $saldo -= (float) ($inscripcion->monto_inscripcion ?? 0);

            // 4. Guardamos el saldo final
            $inscripcion->saldo = max(0, round($saldo, 2));
        });
    }

    public function pagos()
    {
        return $this->hasMany(Pagos::class, 'alumno_id', 'id');
    }

    public function diplomado()
    {
        return $this->belongsTo(Diplomado::class, 'diplomado_id', 'id');
    }

    public function usuarioTutor()
    {
        return $this->belongsTo(User::class, 'tutor', 'id');
    }

    public function usuarioAsesor()
    {
        return $this->belongsTo(User::class, 'asesor', 'id');
    }

    public function descuento()
    {
        return $this->belongsTo(Descuento::class, 'descuento_id');
    }
}
