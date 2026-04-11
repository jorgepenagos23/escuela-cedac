<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Descuento extends Model
{
    use HasFactory;

    protected $table = 'descuentos';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'valor',
        'aplica_a', 'diplomado_id', 'tutor_id',
        'fecha_inicio', 'fecha_fin', 'estado', 'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'valor'        => 'float',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function diplomado()
    {
        return $this->belongsTo(Diplomado::class, 'diplomado_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Descuentos vigentes hoy y con estado activo */
    public function scopeVigentes($query)
    {
        $hoy = Carbon::today();
        return $query->where('estado', 'activo')
                     ->where('fecha_inicio', '<=', $hoy)
                     ->where('fecha_fin',    '>=', $hoy);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Aplica el descuento a un monto y devuelve el resultado */
    public function aplicar(float $monto): float
    {
        if ($this->tipo === 'porcentaje') {
            return round($monto - ($monto * $this->valor / 100), 2);
        }
        return max(0, round($monto - $this->valor, 2));
    }

    /** Devuelve el ahorro sobre un monto */
    public function ahorro(float $monto): float
    {
        return round($monto - $this->aplicar($monto), 2);
    }

    public function getEtiquetaAttribute(): string
    {
        return $this->tipo === 'porcentaje'
            ? "{$this->valor}%"
            : '$' . number_format($this->valor, 2) . ' MXN';
    }

    public function getVigenteAttribute(): bool
    {
        $hoy = Carbon::today();
        return $this->estado === 'activo'
            && $this->fecha_inicio <= $hoy
            && $this->fecha_fin   >= $hoy;
    }
}
