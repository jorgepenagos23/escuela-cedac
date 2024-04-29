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
}
