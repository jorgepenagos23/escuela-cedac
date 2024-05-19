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
        'duracion_mes',
        'requisitos',
        'costo_total',

    ];
    public function grupoCampañas()
    {
        return $this->hasMany(GrupoCampaña::class, 'diplomado_id', 'id');
    }
}
