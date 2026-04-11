<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['diploma_id', 'nombre', 'costo_total', 'numero_pagos', 'intervalo_dias'];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
