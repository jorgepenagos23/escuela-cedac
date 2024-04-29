<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory ;

    protected $table ='alumnos';

    protected $fillable = [
    'id',
    'nombre_completo',
    'matricula',
    'fecha_nacimiento',
    'correo',
    'telefono',
    'direccion',
    'id_diplomado',
    ];





}
