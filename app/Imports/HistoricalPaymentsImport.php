<?php

namespace App\Imports;

use App\Models\Inscripcion;
use App\Models\Diplomado;
use App\Models\User;
use App\Models\GrupoCampaña;
use App\Models\CuentadeDeposito;
use App\Models\Pagos;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class HistoricalPaymentsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $isFirst = true;
        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $nombre = trim($row[1] ?? '');
            if (!$nombre) continue;

            $diplomado = $this->getOrCreateDiplomado($row[2] ?? '');
            
            // 3. Buscar/Crear Inscripción por nombre y diplomado (Deduplicación)
            $insc = Inscripcion::where('nombre_alumno', mb_strtoupper($nombre))
                               ->where('diplomado_id', $diplomado->id)
                               ->first();

            if (!$insc) {
                $campaña_nombre = $row[3] ?? 'Importación Historico';
                $grupo = GrupoCampaña::firstOrCreate(
                    ['campaña' => $campaña_nombre, 'id_diplomado' => $diplomado->id],
                    ['grupo' => 'G-HISTORICO']
                );

                $insc = Inscripcion::create([
                    'fecha_inscripcion' => $this->transformDate($row[6] ?? now()),
                    'nombre_alumno'     => mb_strtoupper($nombre),
                    'diplomado_id'      => $diplomado->id,
                    'monto_inscripcion' => $this->transformCurrency($row[5] ?? 0),
                    'monto_descuento'   => $this->transformCurrency($row[18] ?? 0),
                    'asesor'            => $this->findUserId($row[4] ?? ''),
                    'grupo_campa'       => $grupo->id,
                    'cuentadeposito'    => CuentadeDeposito::first()->id ?? 1,
                    'estado'            => 'Activo',
                    'tutor'             => auth()->id() ?? 1,
                 ]);
            }

            // 4. Procesar Pagos Adicionales (1-4 e Importe final)
            // Indices: 7 (1), 9 (2), 11 (3), 13 (4), 17 (IMPORTE)
            // Fechas en indices pares inmediatos (8, 10, 12) o compartido (15)
            $paymentCols = [
                ['monto' => 7,  'fecha' => 8],  // Pago 1
                ['monto' => 9,  'fecha' => 10], // Pago 2
                ['monto' => 11, 'fecha' => 12], // Pago 3
                ['monto' => 13, 'fecha' => 15], // Pago 4
                ['monto' => 17, 'fecha' => 15], // Importe final
            ];

            foreach ($paymentCols as $col) {
                $monto = $this->transformCurrency($row[$col['monto']] ?? 0);
                if ($monto > 0) {
                    $fecha = $this->transformDate($row[$col['fecha']] ?? $insc->fecha_inscripcion);
                    $this->createPaymentIfNotExists($insc, $monto, $fecha);
                }
            }
        }
    }

    private function getOrCreateDiplomado($nombre)
    {
        $nombre = trim($nombre);
        $diplomado = Diplomado::where('nombre', 'like', "%$nombre%")->first();
        if (!$diplomado && $nombre) {
            $diplomado = Diplomado::create(['nombre' => $nombre, 'costo_total' => 7000, 'duracion_mes' => 6]);
        }
        return $diplomado ?: Diplomado::first() ?: Diplomado::create(['nombre' => 'Diplomado Genérico', 'costo_total' => 7000]);
    }

    private function findUserId($name)
    {
        if (!$name) return 1;
        $user = User::where('name', 'like', "%$name%")->first();
        return $user ? $user->id : 1;
    }

    private function createPaymentIfNotExists($insc, $monto, $fecha)
    {
        $exists = Pagos::where('alumno_id', $insc->id)
                       ->where('pago_colegiatura', $monto)
                       ->whereDate('Fecha_PrimerContacto', $fecha)
                       ->exists();

        if (!$exists) {
            Pagos::create([
                'Fecha_PrimerContacto' => $fecha,
                'pago_colegiatura'     => $monto,
                'status'               => 'Pagado',
                'alumno_id'            => $insc->id,
                'diplomado_id'         => $insc->diplomado_id,
                'tutor'                => auth()->id() ?? 1,
                'cuentadeposito'       => $insc->cuentadeposito,
            ]);
        }
    }

    private function transformDate($value)
    {
        if (!$value) return now()->format('Y-m-d');
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d');
        }
    }

    private function transformCurrency($value)
    {
        if (!$value || $value === '-') return 0.0;
        return (float) str_replace(['$', ',', ' '], '', $value);
    }
}
