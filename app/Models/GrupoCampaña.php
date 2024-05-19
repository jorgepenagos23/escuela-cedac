<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoCampaña extends Model
{
    use HasFactory;
    protected $table = "grupo_campañas";
    protected $fillable = [
            "id",
            "campaña",
            "id_diplomado",


    ] ;


    public function diplomado(){


        return $this->belongsTo(Diplomado::class, 'id_diplomado','id');
    }



}
