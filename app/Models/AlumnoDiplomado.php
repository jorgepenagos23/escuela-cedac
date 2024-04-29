<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumnoDiplomado extends Model
{
    use HasFactory;

    protected $table = 'alumnos_diplomado';

    protected $fillable = [
        'alumno_id',
        'diplomado_id',
    ];
}
