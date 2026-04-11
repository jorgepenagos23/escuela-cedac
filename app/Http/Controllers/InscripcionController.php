<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Pagos;
use App\Models\User;
use App\Models\Diplomado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InscripcionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function seguimiento_inscripciones(){
        $user = User::find(auth()->user()->id);
        return Inertia::render('Inscripciones_Seguimiento', ['userId' => $user->id]);
    }

    /**
     * Vista del buscador de alumnos
     */
    public function buscadorAlumnos()
    {
        return Inertia::render('BuscadorAlumnos');
    }

    /**
     * API: busca alumnos por nombre, celular o correo (búsqueda en tiempo real)
     */
    public function buscarAlumno(Request $request)
    {
        $q = trim($request->query('q', ''));

        $query = Inscripcion::select(
            'alumno_inscripcion.id',
            'alumno_inscripcion.nombre_alumno',
            'alumno_inscripcion.celular',
            'alumno_inscripcion.adicional',
            'alumno_inscripcion.correo',
            'alumno_inscripcion.curp',
            'alumno_inscripcion.estado',
            'alumno_inscripcion.municipio',
            'alumno_inscripcion.direccion_completa',
            'alumno_inscripcion.nombre_emergencia',
            'alumno_inscripcion.parentesco_emergencia',
            'alumno_inscripcion.saldo',
            'alumno_inscripcion.monto_inscripcion',
            'alumno_inscripcion.fecha_inscripcion',
            'alumno_inscripcion.fecha_primer_pago_colegiatura',
            'alumno_inscripcion.metodo_pago_inscripcion',
            'alumno_inscripcion.plan_pagos',
            'alumno_inscripcion.diplomado_id',
            'alumno_inscripcion.grupo_campa',
            'alumno_inscripcion.tutor',
            'alumno_inscripcion.asesor',
            'diplomados.nombre as nombre_diplomado',
            'diplomados.costo_total',
            'diplomados.duracion_mes',
            'users.name as tutor_nombre',
            'asesores.name as asesor_nombre',
            DB::raw('gc.grupo as grupo'),
            DB::raw('gc.`campaña` as campana'),
            'alumno_inscripcion.descuento_id',
            'alumno_inscripcion.monto_descuento',
            'descuentos.nombre as descuento_nombre'
        )
        ->leftJoin('descuentos', 'descuentos.id', '=', 'alumno_inscripcion.descuento_id')
        ->leftJoin('diplomados', 'diplomados.id', '=', 'alumno_inscripcion.diplomado_id')
        ->leftJoin('users', 'users.id', '=', 'alumno_inscripcion.tutor')
        ->leftJoin('users as asesores', 'asesores.id', '=', 'alumno_inscripcion.asesor')
        ->leftJoin(DB::raw('`grupo_campañas` as gc'), 'gc.id', '=', 'alumno_inscripcion.grupo_campa');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('alumno_inscripcion.nombre_alumno', 'LIKE', "%{$q}%")
                    ->orWhere('alumno_inscripcion.celular', 'LIKE', "%{$q}%")
                    ->orWhere('alumno_inscripcion.correo', 'LIKE', "%{$q}%")
                    ->orWhere('alumno_inscripcion.curp', 'LIKE', "%{$q}%")
                    ->orWhere('alumno_inscripcion.id', 'LIKE', "%{$q}%");
            });
        }

        $alumnos = $query->orderBy('alumno_inscripcion.nombre_alumno')->limit(40)->get()
            ->map(function ($a) {
                $a->plan_pagos = $a->plan_pagos ? json_decode($a->plan_pagos, true) : [];
                return $a;
            });

        return response()->json(['alumnos' => $alumnos]);
    }

    /**
     * API: actualiza datos generales + plan de pagos de un alumno
     */
    public function actualizarAlumno(Request $request, $id)
    {
        $inscripcion = Inscripcion::findOrFail($id);

        $inscripcion->nombre_alumno          = $request->input('nombre_alumno',          $inscripcion->nombre_alumno);
        $inscripcion->celular                = $request->input('celular',                $inscripcion->celular);
        $inscripcion->adicional              = $request->input('adicional',              $inscripcion->adicional);
        $inscripcion->correo                 = $request->input('correo',                 $inscripcion->correo);
        $inscripcion->curp                   = $request->input('curp',                   $inscripcion->curp);
        $inscripcion->estado                 = $request->input('estado',                 $inscripcion->estado);
        $inscripcion->municipio              = $request->input('municipio',              $inscripcion->municipio);
        $inscripcion->direccion_completa     = $request->input('direccion_completa',     $inscripcion->direccion_completa);
        $inscripcion->nombre_emergencia      = $request->input('nombre_emergencia',      $inscripcion->nombre_emergencia);
        $inscripcion->parentesco_emergencia  = $request->input('parentesco_emergencia',  $inscripcion->parentesco_emergencia);
        $inscripcion->metodo_pago_inscripcion= $request->input('metodo_pago_inscripcion',$inscripcion->metodo_pago_inscripcion);
        $inscripcion->fecha_primer_pago_colegiatura = $request->input('fecha_primer_pago_colegiatura', $inscripcion->fecha_primer_pago_colegiatura);

        // Actualizar plan de pagos si viene en el request
        if ($request->has('plan_pagos')) {
            $inscripcion->plan_pagos = json_encode($request->input('plan_pagos'));
        }

        $inscripcion->save();

        return response()->json([
            'message'     => 'Datos del alumno actualizados correctamente.',
            'inscripcion' => $inscripcion,
        ]);
    }




     public function inscripciones(){

        return Inertia::render("Inscripciones");
    }

    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Inscripcion::select(
            'alumno_inscripcion.*',
            'diplomados.nombre as nombre_diplomado',
            'cuenta_deposito.titular as Titular',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.numero_cuenta as numero_cuenta',
            'cuenta_deposito.CLABE as CLABE',
            'cuenta_deposito.banco as Banco',

            'users.name as Tutor',
            'tutores.name as Asesor'
        )
        ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
        ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor')
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->join('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
        ->where('alumno_inscripcion.saldo', '>', 0);
        
        if ($startDate && $endDate) {
            $query->whereBetween('alumno_inscripcion.fecha_inscripcion', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('alumno_inscripcion.fecha_inscripcion', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('alumno_inscripcion.fecha_inscripcion', '<=', $endDate);
        }

        $AlumnosConInscripciones = $query->orderBy('alumno_inscripcion.created_at', 'desc')->get();

        return response()->json([
            'alumnos_inscripcion' => $AlumnosConInscripciones,
            'mesagge' => 'Exito en consulta',
            'code' => 200,
        ]);
    }


    public function index2() /// listar para pagos mensualidades
    {
        $AlumnosEstadoPagar = Inscripcion::select(
            'alumno_inscripcion.id as alumno_id',
            'alumno_inscripcion.diplomado_id as diplomado_id',
            'alumno_inscripcion.saldo as Pendiente_Pagar',
            'alumno_inscripcion.nombre_alumno as nombre_completo',
            'alumno_inscripcion.fecha_inscripcion as fecha_inscripcion',
            'alumno_inscripcion.grupo_campa as grupo_campa',
            'alumno_inscripcion.monto_inscripcion as monto_inscripcion',
            'grupo_campañas.campaña as campaña',
            'grupo_campañas.grupo as grupo',
            'diplomados.nombre as nombre_diplomado',
            'cuenta_deposito.titular  as Titular',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.numero_cuenta as numero_cuenta',
            'cuenta_deposito.CLABE as CLABE',
            'cuenta_deposito.banco as Banco',
            'users.name as Tutor',
            'tutores.name as Asesor'

        )
        ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
        ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor') // Cambia 'otro_usuario_id' por el nombre correcto de la columna
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->join('grupo_campañas', 'grupo_campañas.id', '=', 'alumno_inscripcion.grupo_campa')
        ->join('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
        ->where('alumno_inscripcion.saldo', '>',0)
        ->get();





        return response()->json([
            'AlumnosEstadoPagar'=> $AlumnosEstadoPagar,
            'mesagge'=>'Exito en consulta ',
            'code'=> 200,
        ]);
    }

    public function sumaIncripciones()
    {
        $sumaIncripciones = Inscripcion::select(
            'diplomados.id as id_diplomado',
            'diplomados.nombre as nombre_diplomado',
            DB::raw("DATE_FORMAT(alumno_inscripcion.fecha_inscripcion, '%Y-%m') as MesAnio"),
            DB::raw('COUNT(alumno_inscripcion.id) as TotalInscritos'), // Contar el número de inscritos
            DB::raw('GROUP_CONCAT(alumno_inscripcion.fecha_inscripcion) as FechaInscrito'), // Concatenar fechas de inscripción
            DB::raw('SUM(alumno_inscripcion.monto_inscripcion) as TotalInscripcion') // Sumar el monto de inscripción
        )
        ->join('diplomados', 'diplomados.id', '=', 'alumno_inscripcion.diplomado_id')
        ->orderByDesc('TotalInscripcion')
        ->groupBy('diplomados.id', 'diplomados.nombre', 'MesAnio') // Agrupar también por MesAnio
        ->get();

        return response()->json([
            'sumaIncripciones' => $sumaIncripciones
        ]);
    }

    public function MatriculasActivas(){

        $matriculaActivas = Pagos::select(

            'pagos_colegiatura.status as status',
            DB::raw('COUNT(status) as Activas' ),

             )
             ->where('status','Activo')
             ->groupBy('pagos_colegiatura.status')
             ->get();


                return response()->json([

                    'data' => $matriculaActivas,
                    'message' => 'Matriculas Activas Devueltas'
                ]);

    }


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [


        ];


        try {
            $request->validate($rules);

            $inscripcion = new Inscripcion();

            $inscripcion->fecha_primer_pago_colegiatura = $request->input('fecha_primer_pago_colegiatura');
            $inscripcion->nombre_alumno = $request->input('nombre_alumno');
            $inscripcion->celular = $request->input('celular');
            $inscripcion->adicional = $request->input('adicional');
            $inscripcion->asesor = $request->input('asesor');
            $inscripcion->tutor = $request->input('tutor');
            $inscripcion->grupo_campa = $request->input('grupo_campa');
            $inscripcion->fecha_inscripcion = $request->input('fecha_inscripcion');
            $inscripcion->cuentadeposito = $request ->input('cuentadeposito');
            $inscripcion->monto_inscripcion = $request ->input('monto_inscripcion');
            $inscripcion->diplomado_id = $request ->input('diplomado_id');
            
            // Nuevos Campos de Admisiones
            $inscripcion->correo = $request->input('correo');
            $inscripcion->curp = $request->input('curp');
            $inscripcion->nombre_emergencia = $request->input('nombre_emergencia');
            $inscripcion->parentesco_emergencia = $request->input('parentesco_emergencia');
            $inscripcion->estado = $request->input('estado');
            $inscripcion->municipio = $request->input('municipio');
            $inscripcion->direccion_completa = $request->input('direccion_completa');
            $inscripcion->metodo_pago_inscripcion = $request->input('metodo_pago_inscripcion');
            $inscripcion->descuento_id = $request->input('descuento_id');

            $inscripcion->save();

            // ── Generar Plan de Pagos Automático ──────────────────────────────
            // El boot() ya calculó el saldo (costo_total - monto_inscripcion)
            $inscripcion->refresh();

            $diplomado       = \App\Models\Diplomado::find($inscripcion->diplomado_id);
            $numCuotas       = ($diplomado && $diplomado->duracion_mes > 0) ? (int) $diplomado->duracion_mes : 5;
            $saldoTotal      = (float) $inscripcion->saldo;
            $fechaBase       = $inscripcion->fecha_primer_pago_colegiatura
                               ? \Carbon\Carbon::parse($inscripcion->fecha_primer_pago_colegiatura)
                               : \Carbon\Carbon::now()->addMonth();

            $montoPorCuota    = $numCuotas > 1 ? floor($saldoTotal / $numCuotas * 100) / 100 : $saldoTotal;
            $montoUltimaCuota = round($saldoTotal - $montoPorCuota * ($numCuotas - 1), 2);

            $planPagos = [];
            for ($i = 0; $i < $numCuotas; $i++) {
                $montoEsta = ($i < $numCuotas - 1) ? $montoPorCuota : $montoUltimaCuota;
                $planPagos[] = [
                    'numero'      => $i + 1,
                    'fecha'       => $fechaBase->copy()->addMonths($i)->format('Y-m-d'),
                    'monto'       => $montoEsta,
                    'descripcion' => "Mensualidad " . ($i + 1) . " de " . $numCuotas,
                    'estado'      => 'pendiente',
                ];
            }

            $inscripcion->plan_pagos = json_encode($planPagos);
            $inscripcion->save();
            // ─────────────────────────────────────────────────────────────────

            return response()->json([
                 'message'    => 'Inscripción guardada exitosamente.',
                 'inscripcion' => $inscripcion,
                 'plan_pagos'  => $planPagos,
                ], 200);

        } catch (\Throwable $th) {

            return response()->json([
                'error'=> $th->getMessage()


            ],400);

        }


    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function imprimirFichaInscripcion($id)
    {
        $inscripcion = Inscripcion::with(['diplomado', 'usuarioAsesor', 'usuarioTutor'])->findOrFail($id);

        $grupoData = \App\Models\GrupoCampaña::find($inscripcion->grupo_campa);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ficha_inscripcion', compact('inscripcion', 'grupoData'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('Ficha_Inscripcion_' . str_replace(' ', '_', $inscripcion->nombre_alumno) . '.pdf');
    }
}
