<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'campaign_id', 'fecha_inscripcion'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }
}
