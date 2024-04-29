<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentadeDeposito extends Model
{
    use HasFactory;

    protected $table='cuenta_deposito';

    protected $fillable=[
        'id',
        'CLABE',
        'numero_cuenta',
        'titular',
        'banco',
    ];
}
