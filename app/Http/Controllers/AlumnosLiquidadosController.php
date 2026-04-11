<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Inscripcion;

class AlumnosLiquidadosController extends Controller
{
    public function indexView()
    {
        return Inertia::render('AlumnosLiquidados');
    }

    public function getData()
    {
        $alumnosLiquidados = Inscripcion::select(
            'alumno_inscripcion.id as matricula',
            'alumno_inscripcion.nombre_alumno as nombre_completo',
            'diplomados.nombre as nombre_diplomado',
            'alumno_inscripcion.fecha_inscripcion as fecha_apertura',
            \DB::raw("MAX(pagos_colegiatura.created_at) as fecha_liquidacion"),
            'alumno_inscripcion.celular as telefono',
            'alumno_inscripcion.adicional as correo'
        )
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->leftJoin('pagos_colegiatura', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
        ->groupBy(
            'alumno_inscripcion.id',
            'alumno_inscripcion.nombre_alumno',
            'diplomados.nombre',
            'diplomados.costo_total',
            'alumno_inscripcion.monto_inscripcion',
            'alumno_inscripcion.fecha_inscripcion',
            'alumno_inscripcion.celular',
            'alumno_inscripcion.adicional'
        )
        // Regla antifraude: Solo los que matemáticamente han superado el costo total
        ->havingRaw('COALESCE(SUM(pagos_colegiatura.pago_colegiatura), 0) + alumno_inscripcion.monto_inscripcion >= diplomados.costo_total')
        ->orderBy('fecha_liquidacion', 'desc')
        ->get();

        return response()->json([
            'alumnos' => $alumnosLiquidados
        ]);
    }

    public function getExpediente(Request $request) {
        $nombre = $request->query('nombre');
        
        $inscripciones = Inscripcion::with(['diplomado', 'pagos.usuarioTutor', 'usuarioTutor', 'usuarioAsesor'])
            ->where('nombre_alumno', $nombre)
            ->orderBy('fecha_inscripcion', 'desc')
            ->get();
            
        // Regla antifraude: Reemplazar el saldo por su matemático real
        foreach ($inscripciones as $inscripcion) {
            $totalPagos = $inscripcion->pagos->sum('pago_colegiatura');
            $costoTotal = $inscripcion->diplomado->costo_total;
            $saldoReal = $costoTotal - ($inscripcion->monto_inscripcion + $totalPagos);
            
            if ($saldoReal < 0) {
                $saldoReal = 0;
            }
            $inscripcion->saldo = $saldoReal;
        }

        return response()->json([
            'expediente' => $inscripciones
        ]);
    }

    public function imprimirCertificado($inscripcion_id) {
        $inscripcion = Inscripcion::with(['diplomado', 'pagos'])->findOrFail($inscripcion_id);
        
        $totalPagos = $inscripcion->pagos->sum('pago_colegiatura');
        $costoTotal = $inscripcion->diplomado->costo_total;
        $saldoReal = $costoTotal - ($inscripcion->monto_inscripcion + $totalPagos);
        
        // Verificar matemáticamente que realmente esté liquidado
        if ($saldoReal > 0) {
            return abort(403, 'Antifraude: El alumno cuenta con un adeudo pendiente calculado en $' . number_format($saldoReal, 2) . '. No se expide el certificado.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certificado', compact('inscripcion'));
        $pdf->setPaper('letter', 'landscape');
        return $pdf->stream('Certificado_' . str_replace(' ', '_', $inscripcion->nombre_alumno) . '.pdf');
    }
}
