<?php

namespace App\Imports;

use App\Models\Inscripcion;
use App\Models\Diplomado;
use App\Models\User;
use App\Models\CuentadeDeposito;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AdmissionsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $isFirst = true;
        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $nombre = trim($row[2] ?? '');
            if (!$nombre) continue;

            $diplomado = $this->getOrCreateDiplomado($row[3] ?? '');

            // 3. Grupo campaña obligatorio (no es nulo en BD)
            $grupo = \App\Models\GrupoCampaña::firstOrCreate(
                ['campaña' => 'Admisiones Excel', 'id_diplomado' => $diplomado->id],
                ['grupo' => 'G-ADMISIONES']
            );

            // Mapeo Formato A:
            // 2: Nombre, 3: Diplomado, 6: Asesor, 7: Tutor, 8: Estatus, 9: Colegiatura, 10: Obs Tutorias, 11: Obs Admisiones, 12: Telefono
            Inscripcion::create([
                'fecha_inscripcion'      => $this->transformDate($row[1] ?? now()),
                'nombre_alumno'          => mb_strtoupper($nombre),
                'diplomado_id'           => $diplomado->id,
                'asesor'                 => $this->findUserId($row[6] ?? ''),
                'tutor'                  => $this->findUserId($row[7] ?? ''),
                'estado'                 => 'Activo',
                'estatus_excel'          => $row[8] ?? null,
                'monto_inscripcion'      => $this->transformCurrency($row[9] ?? 0),
                'observacion_tutorias'   => $row[10] ?? null,
                'observacion_admisiones' => $row[11] ?? null,
                'celular'                => $row[12] ?? null,
                'cuentadeposito'         => CuentadeDeposito::first()->id ?? 1,
                'grupo_campa'            => $grupo->id,
            ]);
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
