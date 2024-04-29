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


    ];


}
