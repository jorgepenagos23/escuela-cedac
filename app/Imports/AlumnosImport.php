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


class AlumnosImport implements ToCollection
{
    /**
    * @param Collection $rows
    */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) return;

        // 1. Detectar el formato basándonos en la primera fila
        $header = $rows->first();
        $isFormatB = (bool) (str_contains(mb_strtolower($header[0] ?? ''), 'no') || str_contains(mb_strtolower($header[1] ?? ''), 'nombre del alumno'));
        
        // Saltamos encabezados
        $isFirst = true;

        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            if ($isFormatB) {
                $this->processFormatB($row); // El nuevo (Historial con pagos 1, 2, 3, 4)
            } else {
                $this->processFormatA($row); // El tradicional (Inscripción simple)
            }
        }
    }

    /**
     * FORMATO A: Tradicional / Inscripción Simple
     * Índices: 2 (Nombre), 3 (Diplomado), 6 (Asesor), 7 (Tutor), 9 (Colegiatura/Monto Insc), 12 (Telefono)
     */
    private function processFormatA($row)
    {
        $nombre_alumno = trim($row[2] ?? '');
        if (!$nombre_alumno) return;

        $diplomado_nombre = trim($row[3] ?? '');
        $diplomado = $this->getOrCreateDiplomado($diplomado_nombre);

        $inscripcion = $this->getOrCreateInscripcion($nombre_alumno, $diplomado, [
            'fecha_inscripcion' => $this->transformDate($row[1] ?? now()),
            'monto_inscripcion' => $this->transformCurrency($row[9] ?? 0),
            'asesor'            => $this->findUserId($row[6] ?? ''),
            'tutor'             => $this->findUserId($row[7] ?? ''),
            'celular'           => $row[12] ?? null,
        ]);
    }

    /**
     * FORMATO B: Nuevo / Historial con Pagos 1, 2, 3, 4 y Adicionales
     * Índices: 1 (Nombre), 2 (Diplomado), 3 (Campaña), 4 (Asesor), 5 (INSC), 6 (FECHA INSC)
     * 7, 9, 11, 13 (Pagos 1-4 con sus respectivas fechas en 8, 10, 12, 14)
     * 18 (Importe adicional) y 16 (Fecha para ese importe)
     */
    private function processFormatB($row)
    {
        $nombre_alumno = trim($row[1] ?? '');
        if (!$nombre_alumno) return;

        $diplomado_nombre = trim($row[2] ?? '');
        $diplomado = $this->getOrCreateDiplomado($diplomado_nombre);

        // Grupo campaña por nombre
        $campaña_nombre = $row[3] ?? 'Importación Historico';
        $grupo = GrupoCampaña::firstOrCreate(
            ['campaña' => $campaña_nombre, 'id_diplomado' => $diplomado->id],
            ['grupo' => 'G-HISTORICO']
        );

        // 5. Crear Inscripción con el monto base
        $monto_insc = $this->transformCurrency($row[5] ?? 0);
        $fecha_insc = $this->transformDate($row[6] ?? now());

        $inscripcion = $this->getOrCreateInscripcion($nombre_alumno, $diplomado, [
            'fecha_inscripcion' => $fecha_insc,
            'monto_inscripcion' => $monto_insc,
            'asesor'            => $this->findUserId($row[4] ?? ''),
            'grupo_campa'       => $grupo->id,
        ]);

        // 6. Procesar Pagos Adicionales (Mensualidades 1, 2, 3, 4 y el campo IMPORTE final)
        $paymentCols = [
            ['monto' => 7,  'fecha' => 8],
            ['monto' => 9,  'fecha' => 10],
            ['monto' => 11, 'fecha' => 12],
            ['monto' => 13, 'fecha' => 14],
            ['monto' => 18, 'fecha' => 16], // Último importe reportado
        ];

        foreach ($paymentCols as $col) {
            $monto = $this->transformCurrency($row[$col['monto']] ?? 0);
            if ($monto > 0) {
                // Si no hay fecha para el pago, usamos la de la inscripción como base
                $fechaVal = $row[$col['fecha']] ?? null;
                $fecha = $this->transformDate($fechaVal ?: $fecha_insc);
                
                $this->createPaymentIfNotExists($inscripcion, $monto, $fecha);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getOrCreateDiplomado($nombre)
    {
        $diplomado = Diplomado::where('nombre', 'like', '%' . $nombre . '%')->first();
        if (!$diplomado && $nombre) {
            $diplomado = Diplomado::create([
                'nombre' => $nombre,
                'costo_total' => 7000,
                'duracion_mes' => 6
            ]);
        }
        return $diplomado ?: Diplomado::first() ?: Diplomado::create(['nombre' => 'Diplomado Genérico', 'costo_total' => 7000]);
    }

    private function getOrCreateInscripcion($nombre, $diplomado, $defaults)
    {
        $nombre = mb_strtoupper($nombre);
        $insc = Inscripcion::where('nombre_alumno', $nombre)
                           ->where('diplomado_id', $diplomado->id)
                           ->first();

        if (!$insc) {
            $cuenta = CuentadeDeposito::first();
            $data = array_merge([
                'nombre_alumno' => $nombre,
                'diplomado_id'  => $diplomado->id,
                'cuentadeposito' => $cuenta ? $cuenta->id : 1,
                'estado'        => 'Activo',
                'tutor'         => auth()->id() ?? 1,
            ], $defaults);
            $insc = Inscripcion::create($data);
        }
        return $insc;
    }

    private function createPaymentIfNotExists($insc, $monto, $fecha)
    {
        // Evitar duplicados del mismo monto el mismo día para el mismo alumno
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

    private function findUserId($name)
    {
        if (!$name) return 1;
        $user = User::where('name', 'like', "%$name%")->first();
        return $user ? $user->id : 1;
    }

    private function transformDate($value)
    {
        if (!$value) return now()->format('Y-m-d');
        try {
            if (is_numeric($value)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
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
