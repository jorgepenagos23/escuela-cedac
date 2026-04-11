<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['enrollment_id', 'numero_exhibicion', 'monto_esperado', 'fecha_vencimiento', 'estado'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    // Configuración estricta de auditoría
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['monto_esperado', 'estado']) // Solo interceptamos estos cambios críticos
            ->logOnlyDirty() // Evita logs si guardan sin cambiar nada
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "El registro de pago ha sido {$eventName}");
    }
}
