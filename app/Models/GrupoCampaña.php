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
            "grupo",
            "id_diplomado",
            "tutor_id",


    ] ;


    public function diplomado(){
        return $this->belongsTo(Diplomado::class, 'id_diplomado','id');
    }

    public function tutor(){
        return $this->belongsTo(User::class, 'tutor_id', 'id');
    }



}
