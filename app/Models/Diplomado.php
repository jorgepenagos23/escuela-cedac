<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diplomado extends Model
{
    use HasFactory;
   protected $table = 'diplomados';
    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'duracion_mes',
        'requisitos',
        'costo_total',
        
    ];

}
