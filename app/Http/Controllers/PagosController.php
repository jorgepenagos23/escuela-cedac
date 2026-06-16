<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Pagos;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function show_sinpago($id) {
        $pagosColegiaturaAlumno2 = Inscripcion::leftJoin("pagos_colegiatura", "alumno_inscripcion.id", "=", "pagos_colegiatura.alumno_id")
            ->whereNull("pagos_colegiatura.alumno_id")
            ->where("alumno_inscripcion.id", $id) // Filtra por el ID
            ->select('alumno_inscripcion.*')
            ->get();

        return response()->json([
            'pagosColegiaturaAlumno2' => $pagosColegiaturaAlumno2
        ]);
    }

    public function pendientesPagar(Request $request) {
        $tutorId = $request->query('tutor_id');

        $query = Inscripcion::leftJoin("pagos_colegiatura", "alumno_inscripcion.id", "=", "pagos_colegiatura.alumno_id")
            ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->whereNull("pagos_colegiatura.alumno_id");
            
        if ($tutorId) {
            $query->where('alumno_inscripcion.tutor', $tutorId);
        }

        $pendienteMesUser = $query->select(
            'alumno_inscripcion.id as alumno_id',
            'alumno_inscripcion.nombre_alumno', 
            'alumno_inscripcion.saldo',
            'alumno_inscripcion.celular',
            'alumno_inscripcion.adicional',
            'alumno_inscripcion.fecha_inscripcion',
            'alumno_inscripcion.monto_inscripcion',
            'diplomados.nombre as nombre_diplomado',
            'alumno_inscripcion.diplomado_id as diplomado_id',
            'diplomados.id as id_diplomado'
        )->get();

        // Eliminar el campo original 'id' ya que ahora tenemos 'alumno_id'
        $pendienteMesUser->transform(function ($item) {
            unset($item->id);
            return $item;
        });

        // KPI Data para Dashboard de Rendimiento del Tutor
        $cerradas = 0;
        if ($tutorId) {
            // Contar cuantas inscripciones hechas por ESTE tutor YA tienen al menos 1 pago (Seguimiento concluido)
            $cerradas = Inscripcion::join('pagos_colegiatura', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
                ->where('alumno_inscripcion.tutor', $tutorId)
                ->distinct('alumno_inscripcion.id')
                ->count('alumno_inscripcion.id');
        }

        return response()->json([
            'pendienteMesUser' => $pendienteMesUser,
            'kpi' => [
                'cerradas' => $cerradas,
                'meta' => 20
            ]
        ]);
    }


    public function vistaResumen(){

        return Inertia::render("Resumen");
    }


     public function vistaPagos(){
            return Inertia::render("Pagos");
     }

     public function vistaPagosAgregar(){


        return Inertia::render("AgregarPagosMes");
     }

     public function seguimientotutorias()  {

        $user = User::find(Auth::user()->id);



        return Inertia::render("SeguimientoTutoria", [
            'userId'=> $user->id]);
     }

     public function s() {
        $user = auth()->user();
        return Inertia::render('YourComponentName', [
            'userId' => $user->id,
        ]);
    }

     public function crudPagos(){

        $user = User::find(auth()->user()->id);

        return Inertia::render('PagosMensualidades',
    [    'userId' => $user->id]
    );
     }

     public function vistaContabilidad(){
        $user = User::find(auth()->user()->id);
        return Inertia::render('Contabilidad', ['userId' => $user->id]);
     }

     public function vistaDashboardFinanciero(){
        return Inertia::render('DashboardFinanciero');
     }

     /**
      * DASHBOARD FINANCIERO — Estado de resultados con corte de fechas
      */
     public function dashboardFinanciero(Request $request)
     {
         $desde = $request->get('desde', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
         $hasta = $request->get('hasta', \Carbon\Carbon::now()->format('Y-m-d'));

         // ── 1. Colegiaturas activas en el período ────────────────────────────
         $pagosActivos = Pagos::join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
             ->join('diplomados',      'diplomados.id',      '=', 'pagos_colegiatura.diplomado_id')
             ->join('cuenta_deposito', 'cuenta_deposito.id', '=', 'pagos_colegiatura.cuentadeposito')
             ->join('users',           'users.id',           '=', 'pagos_colegiatura.tutor')
             ->select(
                 'pagos_colegiatura.pago_colegiatura as monto',
                 'diplomados.nombre as diplomado',
                 'cuenta_deposito.banco',
                 'cuenta_deposito.titular as titular',
                 'users.name as cajero'
             )
             ->whereDate('pagos_colegiatura.Fecha_PrimerContacto', '>=', $desde)
             ->whereDate('pagos_colegiatura.Fecha_PrimerContacto', '<=', $hasta)
             ->where('pagos_colegiatura.status', 'activo')
             ->get();

         $totalColegiaturas = $pagosActivos->sum('monto');

         // ── 2. Cancelaciones en el período ───────────────────────────────────
         $pagosCancelados = Pagos::whereDate('Fecha_PrimerContacto', '>=', $desde)
             ->whereDate('Fecha_PrimerContacto', '<=', $hasta)
             ->where('status', 'Cancelado')
             ->get();

         $totalCancelado = $pagosCancelados->sum('pago_colegiatura');

         // ── 3. Inscripciones en el período ───────────────────────────────────
         $inscripcionesPeriodo = Inscripcion::whereDate('fecha_inscripcion', '>=', $desde)
             ->whereDate('fecha_inscripcion', '<=', $hasta)
             ->selectRaw('COUNT(*) as total_alumnos, SUM(monto_inscripcion) as total_monto')
             ->first();

         // ── 4. Desglose por banco ─────────────────────────────────────────────
         $porBanco = $pagosActivos->groupBy('banco')->map(fn($g, $k) => [
             'banco' => $k,
             'monto' => $g->sum('monto'),
             'count' => $g->count(),
         ])->values()->sortByDesc('monto')->values();

         // ── 5. Desglose por diplomado ─────────────────────────────────────────
         $porDiplomado = $pagosActivos->groupBy('diplomado')->map(fn($g, $k) => [
             'diplomado' => $k,
             'monto'     => $g->sum('monto'),
             'count'     => $g->count(),
         ])->values()->sortByDesc('monto')->values();

         // ── 6. Desglose por cajero ─────────────────────────────────────────────
         $porCajero = $pagosActivos->groupBy('cajero')->map(fn($g, $k) => [
             'cajero' => $k,
             'monto'  => $g->sum('monto'),
             'count'  => $g->count(),
         ])->values()->sortByDesc('monto')->values();

         // ── 7. Cartera total activa ────────────────────────────────────────────
         $cartera = Inscripcion::join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
             ->where('alumno_inscripcion.saldo', '>', 0)
             ->selectRaw('
                 COUNT(alumno_inscripcion.id) as alumnos_deudores,
                 SUM(alumno_inscripcion.saldo) as total_cartera
             ')
             ->first();

         // ── 8. Cartera vencida (cálculo matemático) ───────────────────────────
         $todasInscripciones = Inscripcion::with(['pagos'])
             ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
             ->select('alumno_inscripcion.*', 'diplomados.costo_total')
             ->get();

         $hoy         = \Carbon\Carbon::now()->startOfDay();
         $finSemana   = \Carbon\Carbon::now()->endOfWeek();
         $montoVencido = 0; $countVencidos = 0;
         $montoSemana  = 0; $countSemana   = 0;
         $montoProximo = 0; $countProximos = 0;

         foreach ($todasInscripciones as $ins) {
             $importePagado   = $ins->pagos->sum('pago_colegiatura');
             $saldoMatematico = $ins->costo_total - ($ins->monto_inscripcion + $importePagado);
             if ($saldoMatematico <= 0) continue;

             $fechaBase = $ins->fecha_primer_pago_colegiatura ?? $ins->created_at;
             $fechaProx = \Carbon\Carbon::parse($fechaBase)->addMonths($ins->pagos->count())->startOfDay();

             if ($fechaProx->lt($hoy)) {
                 $montoVencido += $saldoMatematico; $countVencidos++;
             } elseif ($fechaProx->between($hoy, $finSemana)) {
                 $montoSemana  += $saldoMatematico; $countSemana++;
             } else {
                 $montoProximo += $saldoMatematico; $countProximos++;
             }
         }

         return response()->json([
             'periodo' => ['desde' => $desde, 'hasta' => $hasta],
             'ingresos' => [
                 'colegiaturas' => $totalColegiaturas,
                 'inscripciones_monto' => (float)($inscripcionesPeriodo->total_monto ?? 0),
                 'inscripciones_count' => (int)($inscripcionesPeriodo->total_alumnos ?? 0),
                 'count_movimientos'   => $pagosActivos->count(),
                 'total'               => $totalColegiaturas + (float)($inscripcionesPeriodo->total_monto ?? 0),
             ],
             'cancelaciones' => [
                 'total' => $totalCancelado,
                 'count' => $pagosCancelados->count(),
             ],
             'ingreso_neto' => $totalColegiaturas + (float)($inscripcionesPeriodo->total_monto ?? 0) - $totalCancelado,
             'cartera' => [
                 'total'            => (float)($cartera->total_cartera ?? 0),
                 'alumnos_deudores' => (int)($cartera->alumnos_deudores ?? 0),
                 'vencida'          => ['monto' => $montoVencido,  'count' => $countVencidos],
                 'esta_semana'      => ['monto' => $montoSemana,   'count' => $countSemana],
                 'proxima'          => ['monto' => $montoProximo,  'count' => $countProximos],
             ],
             'desglose' => [
                 'por_banco'      => $porBanco,
                 'por_diplomado'  => $porDiplomado,
                 'por_cajero'     => $porCajero,
             ],
         ]);
     }

     /**
      * REPORTE CONCILIACIÓN — todos los abonos con filtros opcionales de fecha
      */
     public function reporteConciliacion(Request $request)
     {
         $query = Pagos::select(
             'pagos_colegiatura.id as id',
             'pagos_colegiatura.alumno_id',
             'pagos_colegiatura.pago_colegiatura as monto',
             'pagos_colegiatura.Fecha_PrimerContacto as fecha_operacion',
             'pagos_colegiatura.created_at as fecha_ingreso',
             'pagos_colegiatura.status',
             'alumno_inscripcion.nombre_alumno',
             'alumno_inscripcion.saldo as saldo_actual',
             'diplomados.nombre as diplomado',
             'cuenta_deposito.banco',
             'cuenta_deposito.titular as titular_cuenta',
             'cuenta_deposito.numero_cuenta',
             'users.name as cajero'
         )
         ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
         ->join('diplomados',         'diplomados.id',         '=', 'pagos_colegiatura.diplomado_id')
         ->join('cuenta_deposito',    'cuenta_deposito.id',    '=', 'pagos_colegiatura.cuentadeposito')
         ->join('users',              'users.id',              '=', 'pagos_colegiatura.tutor');

         if ($request->filled('desde')) {
             $query->whereDate('pagos_colegiatura.Fecha_PrimerContacto', '>=', $request->desde);
         }
         if ($request->filled('hasta')) {
             $query->whereDate('pagos_colegiatura.Fecha_PrimerContacto', '<=', $request->hasta);
         }
         if ($request->filled('status')) {
             $query->where('pagos_colegiatura.status', $request->status);
         } else {
             $query->whereIn('pagos_colegiatura.status', ['activo', 'Cancelado']);
         }

         $pagos = $query->orderBy('pagos_colegiatura.Fecha_PrimerContacto', 'desc')->get();

         return response()->json([
             'pagos'        => $pagos,
             'total'        => $pagos->where('status', 'activo')->sum('monto'),
             'total_abonos' => $pagos->where('status', 'activo')->count(),
         ]);
     }

     public function reporteContabilidad(Request $request){
        $pagos = Pagos::select(
            'pagos_colegiatura.id as id_pago',
            'pagos_colegiatura.pago_colegiatura as monto',
            'pagos_colegiatura.Fecha_PrimerContacto as fecha_operacion',
            'pagos_colegiatura.created_at as fecha_ingreso_sistema',
            'alumno_inscripcion.nombre_alumno',
            'diplomados.nombre as diplomado',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.titular as titular_cuenta',
            'users.name as cajero'
        )
        ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
        ->join('diplomados', 'diplomados.id', '=', 'pagos_colegiatura.diplomado_id')
        ->join('cuenta_deposito', 'cuenta_deposito.id', '=', 'pagos_colegiatura.cuentadeposito')
        ->join('users', 'users.id', '=', 'pagos_colegiatura.tutor')
        ->where('pagos_colegiatura.status', 'activo')
        ->orderBy('pagos_colegiatura.created_at', 'desc')
        ->get();

        $sumaExito = $pagos->sum('monto');

        return response()->json([
            'pagos' => $pagos,
            'retiro_total' => $sumaExito,
        ]);
     }



        public function historialAlumno1(){



        }
        public function index()
        {
            $pagos = Pagos::select(
                'pagos_colegiatura.*',
                'diplomados.nombre as nombre_diplomado',
                'cuenta_deposito.titular  as Titular',
                'cuenta_deposito.banco as banco',
                'cuenta_deposito.numero_cuenta as numero_cuenta',
                'cuenta_deposito.CLABE as CLABE',
                'alumno_inscripcion.nombre_alumno as Nombre',
                'alumno_inscripcion.saldo as saldo',
                'users.name as Tutor'
            )
                ->join('diplomados', 'pagos_colegiatura.diplomado_id', '=', 'diplomados.id')
                ->join('cuenta_deposito', 'pagos_colegiatura.cuentadeposito', '=', 'cuenta_deposito.id')
                ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
                ->join('users', 'users.id', '=', 'pagos_colegiatura.tutor')
                ->where('alumno_inscripcion.saldo', '>', 0)
                ->where('pagos_colegiatura.status', 'activo')
                ->orderBy('pagos_colegiatura.Fecha_PrimerContacto', 'desc') // Cambio a 'desc'
                ->get();

            return response()->json(
                [
                    'PagosconMensualidades' => $pagos
                ]
            );
        }


    public function sumaPagos()
    {
        // Obtener el mes y año actual
        $currentMonth = date('m');
        $currentYear = date('Y');

        // Obtener el nombre del mes actual
        $currentMonthName = Carbon::now()->monthName;

        // Obtener los pagos correspondientes al mes actual
        $SumaPagos = Pagos::select(
            DB::raw("DATE_FORMAT(pagos_colegiatura.Fecha_PrimerContacto, '%Y-%m') as MesAnio"),
            "diplomados.id as id_Diplomado",
            "diplomados.nombre as Diplomado",
            DB::raw('SUM(pagos_colegiatura.pago_colegiatura) as TotalPagadoAbono')
        )
        ->join("diplomados", "diplomados.id", "=", "pagos_colegiatura.diplomado_id")
        ->whereMonth('pagos_colegiatura.Fecha_PrimerContacto', $currentMonth)
        ->whereYear('pagos_colegiatura.Fecha_PrimerContacto', $currentYear)
        ->groupBy(DB::raw("DATE_FORMAT(pagos_colegiatura.Fecha_PrimerContacto, '%Y-%m')"), "diplomados.id", "diplomados.nombre")
        ->orderByDesc('TotalPagadoAbono')
        ->get();

        return response()->json([
            'SumaPagos' => $SumaPagos,
            'currentMonthName' => $currentMonthName // Enviar el nombre del mes actual al frontend
        ]);
    }


            // ESTA CONSULTA SERIA  PARA VER EL ABONO MENSUAL POR ALUMNO EN FECHAS




            public function AlumnosAbonosTotalesporPeriodo(){
                $AlumnosPagosPendientes = Pagos::select(

                    "diplomados.id as id_Diplomado",
                    "diplomados.nombre as Diplomado",
                    DB::raw('COUNT(pagos_colegiatura.Fecha_PrimerContacto) as TotaldePagos'), // Contar el número de fechas
                    DB::raw('GROUP_CONCAT(pagos_colegiatura.pago_colegiatura) as FechasColegiaturas'),
                    DB::raw('GROUP_CONCAT(pagos_colegiatura.Fecha_PrimerContacto) as Fecha'),
                    'diplomados.costo_total', // Obtener el precio del diplomado sin raw
                    'alumno_inscripcion.monto_inscripcion',
                    DB::raw('SUM(pagos_colegiatura.pago_colegiatura) as Totalpago_colegiatura'),
                    'alumno_inscripcion.saldo as Saldo Pendiente',
                    'alumno_inscripcion.nombre_alumno' // Obtener el nombre del alumno
                )
                ->join("diplomados", "diplomados.id", "=", "pagos_colegiatura.diplomado_id")
                ->join("alumno_inscripcion", function ($join) {
                    $join->on("alumno_inscripcion.diplomado_id", "=", "diplomados.id")
                         ->on("alumno_inscripcion.id", "=", "pagos_colegiatura.alumno_id"); // Agregar unión con la tabla alumno_inscripcion
                })
                ->groupBy("diplomados.id", "alumno_inscripcion.monto_inscripcion", "alumno_inscripcion.nombre_alumno", 'alumno_inscripcion.saldo') // Agrupar por ID de diplomado, monto de inscripción y nombre del alumno
                ->orderByDesc('Totalpago_colegiatura')
                ->where('alumno_inscripcion.saldo','>0')

                ->get();

                // Convertir el campo costo_total a formato numérico sin decimales
                $AlumnosPagosPendientes->transform(function ($item) {
                    $item->costo_total = (float) $item->costo_total;
                    return $item;
                });

                return response()->json([
                    'Alumnos_Abonos_Pagados' => $AlumnosPagosPendientes
                ]);


            }

            public function AlumnosPendientes(){
                $pagosPendientesNetos = Pagos::select(

                    "diplomados.id as id_Diplomado",
                    "diplomados.nombre as Diplomado",
                    DB::raw('COUNT(pagos_colegiatura.Fecha_PrimerContacto) as TotaldePagos'), // Contar el número de fechas
                    DB::raw('GROUP_CONCAT(pagos_colegiatura.pago_colegiatura) as FechasColegiaturas'),
                    DB::raw('GROUP_CONCAT(pagos_colegiatura.Fecha_PrimerContacto) as Fecha'),
                    'diplomados.costo_total', // Obtener el precio del diplomado sin raw
                    'alumno_inscripcion.monto_inscripcion',
                    DB::raw('SUM(pagos_colegiatura.pago_colegiatura) as Totalpago_colegiatura'),
                    'alumno_inscripcion.saldo as Saldo_Pendiente',
                    'alumno_inscripcion.nombre_alumno' ,
                    'alumno_inscripcion.id'
                    // Obtener el nombre del alumno
                )
                ->join("diplomados", "diplomados.id", "=", "pagos_colegiatura.diplomado_id")
                ->join("alumno_inscripcion", function ($join) {
                    $join->on("alumno_inscripcion.diplomado_id", "=", "diplomados.id")
                         ->on("alumno_inscripcion.id", "=", "pagos_colegiatura.alumno_id"); // Agregar unión con la tabla alumno_inscripcion
                })
                ->groupBy("diplomados.id", "alumno_inscripcion.monto_inscripcion", "alumno_inscripcion.nombre_alumno", 'alumno_inscripcion.saldo' , 'alumno_inscripcion.id') // Agrupar por ID de diplomado, monto de inscripción y nombre del alumno
                ->orderByDesc('Totalpago_colegiatura')
                ->where('alumno_inscripcion.saldo', '>', 0) // Filtro para obtener saldos mayores a cero

                ->get();

                // Convertir el campo costo_total a formato numérico sin decimales
                $pagosPendientesNetos->transform(function ($item) {
                    $item->costo_total = (float) $item->costo_total;
                    return $item;
                });

                return response()->json([
                    'pagosPendientesNetos' => $pagosPendientesNetos
                ]);
            }


    /**
     * Show the form for creating a new resource.
     */
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

            $pago = new Pagos();

            $pago->Fecha_PrimerContacto = $request->input('Fecha_PrimerContacto');
            $pago->pago_colegiatura = $request ->input('pago_colegiatura');
            $pago->cuentadeposito = $request ->input('cuentadeposito');
            $pago->diplomado_id = $request ->input('diplomado_id');
            $pago->alumno_id = $request ->input('alumno_id');
            $pago->tutor = $request ->input('tutor');

            if ($request->hasFile('comprobante')) {
                $path = $request->file('comprobante')->store('comprobantes_pagos', 'public');
                $pago->comprobante_path = '/storage/' . $path;
            } else {
                throw new \Exception('Se requiere adjuntar un pdf o imagen como comprobante de pago.');
            }

            $pago->save();


            return response()->json([
                 'message'=> 'inscripcion agregado exisotamente ',
                 'pago' => $pago

                ],200);

        } catch (\Throwable $th) {

            return response()->json([
                'error'=> $th->getMessage()


            ],400);

        }


    }

    public function directorio(Request $request) {
        $AlumnosEstadoPagar = \App\Models\Inscripcion::select(
                'alumno_inscripcion.id as alumno_id',
                'alumno_inscripcion.nombre_alumno as nombre_completo',
                'alumno_inscripcion.saldo as Pendiente_Pagar',
                'alumno_inscripcion.fecha_inscripcion as fecha_inscripcion',
                'alumno_inscripcion.monto_inscripcion as monto_inscripcion',
                'alumno_inscripcion.grupo_campa as grupo_campa',
                'grupo_campañas.campaña as campaña',
                'grupo_campañas.grupo as grupo',
                'diplomados.nombre as nombre_diplomado',
                'diplomados.id as diplomado_id',
                'alumno_inscripcion.created_at as created_at',
                'alumno_inscripcion.updated_at as updated_at',
                'users.name as tutor_nombre',
                'tutores.name as asesor_nombre',
                'cuenta_deposito.banco as banco_registro',
                'cuenta_deposito.titular as titular_registro'
            )
            ->leftJoin('users', 'users.id', '=', 'alumno_inscripcion.tutor')
            ->leftJoin('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor')
            ->leftJoin('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->leftJoin('grupo_campañas', 'grupo_campañas.id', '=', 'alumno_inscripcion.grupo_campa')
            ->leftJoin('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
            ->where('alumno_inscripcion.saldo', '>', 0)
            ->orderBy('alumno_inscripcion.created_at', 'desc')
            ->get();

        return response()->json([
            'AlumnosEstadoPagar' => $AlumnosEstadoPagar,
            'mesagge' => 'Exito en consulta',
            'code' => 200,
        ]);
    }

    /**
     * Display
     *the specified resource.
     */
    public function show($id)
    {
        $pago = Pagos::select(
                'pagos_colegiatura.id as idpago',
                'pagos_colegiatura.alumno_id',
                'pagos_colegiatura.Fecha_PrimerContacto',
                'pagos_colegiatura.pago_colegiatura',
                'pagos_colegiatura.diplomado_id as diplomado_id',

                'cuenta_deposito.titular as Titular',
                'cuenta_deposito.banco as banco',
                'cuenta_deposito.numero_cuenta as numero_cuenta',
                'cuenta_deposito.CLABE as CLABE',
                'alumno_inscripcion.nombre_alumno as nombre_completo',
                'users.name as Tutor',
                'tutores.name as Asesor',
                'diplomados.nombre as nombre_diplomado'
            )
            ->join('diplomados', 'diplomados.id', '=', 'pagos_colegiatura.diplomado_id')

            ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
            ->join('cuenta_deposito', 'pagos_colegiatura.cuentadeposito', '=', 'cuenta_deposito.id')
            ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
            ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor')
            ->where('pagos_colegiatura.alumno_id', $id)
            ->orderBy('pagos_colegiatura.Fecha_PrimerContacto', 'desc')
            ->get();

        return response()->json([
            'pagosColegiaturaAlumno2' => $pago
        ]);
    }


    public function diplomadostotalfecha(Request $request)
    {
        // Obtener la fecha de inicio y fin desde el request
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');

        $diplomadosRecaudoTotal = Pagos::select(
            "diplomados.id as id_Diplomado",
            "diplomados.nombre as Diplomado",
            DB::raw('MIN(pagos_colegiatura.Fecha_PrimerContacto) as FechaInicio'),
            DB::raw('MAX(pagos_colegiatura.Fecha_PrimerContacto) as FechaFin'),
            DB::raw('SUM(pagos_colegiatura.pago_colegiatura) as TotalPagadoAbono')
        )
        ->join("diplomados", "diplomados.id", "=", "pagos_colegiatura.diplomado_id")
        ->whereBetween("pagos_colegiatura.Fecha_PrimerContacto", [$fechaInicio, $fechaFin])
        ->groupBy("diplomados.id", "diplomados.nombre")
        ->orderByDesc('TotalPagadoAbono')
        ->get();

        return response()->json([
            'SumaPagos' => $diplomadosRecaudoTotal
        ]);
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
     * CANCELAR UN ABONO — Revierte el monto al saldo del alumno y marca el pago como 'Cancelado'.
     */
    public function cancelarAbono(Request $request, $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $pago = Pagos::findOrFail($id);

            if ($pago->status === 'Cancelado') {
                return response()->json(['error' => 'Este abono ya fue cancelado anteriormente.'], 422);
            }

            // Devolver el monto al saldo del alumno
            $inscripcion = Inscripcion::findOrFail($pago->alumno_id);
            $inscripcion->saldo += $pago->pago_colegiatura;
            $inscripcion->save();

            // Marcar el pago como Cancelado
            $pago->status = 'Cancelado';
            $pago->motivo_cancelacion = $request->motivo;
            $pago->save();

            DB::commit();

            return response()->json([
                'message'     => 'Abono cancelado correctamente. El saldo del alumno fue restituido.',
                'saldo_nuevo' => $inscripcion->saldo,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET PLAN DE PAGOS — Devuelve el historial de abonos y el esquema de pagos proyectado para un alumno.
     */
    public function getPlanPagos($alumno_id)
    {
        $inscripcion = Inscripcion::with(['pagos', 'diplomado'])->findOrFail($alumno_id);

        $pagosReales = Pagos::where('alumno_id', $alumno_id)
            ->select('id', 'pago_colegiatura', 'Fecha_PrimerContacto', 'status', 'motivo_cancelacion', 'created_at')
            ->orderBy('Fecha_PrimerContacto')
            ->get();

        // Plan de pagos personalizado si existe en columna JSON, si no se genera automático
        $planPersonalizado = $inscripcion->plan_pagos ? json_decode($inscripcion->plan_pagos, true) : null;

        return response()->json([
            'inscripcion' => [
                'id'              => $inscripcion->id,
                'nombre_alumno'   => $inscripcion->nombre_alumno,
                'saldo'           => $inscripcion->saldo,
                'diplomado'       => $inscripcion->diplomado->nombre ?? '',
                'costo_total'     => $inscripcion->diplomado->costo_total ?? 0,
                'monto_inscripcion' => $inscripcion->monto_inscripcion,
            ],
            'pagos_realizados' => $pagosReales,
            'plan_pagos'       => $planPersonalizado,
        ]);
    }

    /**
     * GUARDAR / MODIFICAR PLAN DE PAGOS — Se guarda un esquema de mensualidades personalizado en JSON.
     */
    public function reprogramarPlan(Request $request, $alumno_id)
    {
        $request->validate([
            'plan'               => 'required|array|min:1',
            'plan.*.fecha'       => 'required|date',
            'plan.*.monto'       => 'required|numeric|min:1',
            'plan.*.descripcion' => 'nullable|string|max:200',
        ]);

        $inscripcion = Inscripcion::findOrFail($alumno_id);
        $inscripcion->plan_pagos = json_encode($request->plan);
        $inscripcion->save();

        return response()->json([
            'message'  => 'Plan de pagos actualizado correctamente.',
            'plan'     => $request->plan,
        ]);
    }

    public function generarPdfPago($id)
    {
        $pago = Pagos::select(
            'pagos_colegiatura.id as idpago',
            'pagos_colegiatura.Fecha_PrimerContacto as fecha',
            'pagos_colegiatura.pago_colegiatura as monto',
            'diplomados.nombre as diplomado',
            'cuenta_deposito.titular',
            'cuenta_deposito.banco',
            'cuenta_deposito.CLABE',
            'cuenta_deposito.numero_cuenta',
            'alumno_inscripcion.nombre_alumno as alumno',
            'tutores.name as cajero'
        )
        ->join('diplomados', 'diplomados.id', '=', 'pagos_colegiatura.diplomado_id')
        ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
        ->join('cuenta_deposito', 'pagos_colegiatura.cuentadeposito', '=', 'cuenta_deposito.id')
        ->join('users as tutores', 'tutores.id', '=', 'pagos_colegiatura.tutor')
        ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.pago', compact('pago'));
        return $pdf->download('Recibo_Pago_' . $pago->idpago . '.pdf');
    }

    public function getCalendarioPagos()
    {
        // Traemos todo para evaluarlo matemáticamente y evitar saldos corruptos
        $todasInscripciones = Inscripcion::with(['pagos'])
            ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->select('alumno_inscripcion.*', 'diplomados.nombre as diplomado_nombre', 'diplomados.costo_total')
            ->get();

        $vencidos = [];
        $estaSemana = [];
        $proximos = [];

        $hoy = \Carbon\Carbon::now()->startOfDay();
        $finSemana = \Carbon\Carbon::now()->copy()->endOfWeek();

        foreach ($todasInscripciones as $alumno) {
            
            // Regla Antifraude: Matemática Estricta 
            $importePagado = $alumno->pagos->sum('pago_colegiatura');
            $saldoMatematico = $alumno->costo_total - ($alumno->monto_inscripcion + $importePagado);
            
            // Si matemáticamente ya liquidó, lo ignoramos de la cartera de cobranza
            if ($saldoMatematico <= 0) continue;
            
            // Sobrescribimos el saldo al aire para enviarlo sano a la vista
            $alumno->saldo = $saldoMatematico;

            $cantidadPagos = $alumno->pagos->count();
            
            // Si no tiene fecha configurada, simulamos una
            $fecha_base = $alumno->fecha_primer_pago_colegiatura ? $alumno->fecha_primer_pago_colegiatura : $alumno->created_at;
            
            $fechaProximoPago = \Carbon\Carbon::parse($fecha_base)->addMonths($cantidadPagos)->startOfDay();

            $itemInfo = [
                'id'           => $alumno->id,
                'diplomado_id' => $alumno->diplomado_id,
                'nombre_alumno'=> $alumno->nombre_alumno,
                'diplomado'    => $alumno->diplomado_nombre,
                'saldo'        => $alumno->saldo,
                'celular'      => $alumno->celular,
                'fecha_pago'   => $fechaProximoPago->format('Y-m-d'),
                'dias_retraso' => $hoy->diffInDays($fechaProximoPago, false) * -1
            ];

            if ($fechaProximoPago->lt($hoy)) {
                $vencidos[] = $itemInfo;
            } elseif ($fechaProximoPago->between($hoy, $finSemana)) {
                $estaSemana[] = $itemInfo;
            } else {
                $proximos[] = $itemInfo;
            }
        }

        // Ordenar arrays por fecha_pago ascendente
        usort($vencidos, function ($a, $b) { return strtotime($a['fecha_pago']) - strtotime($b['fecha_pago']); });
        usort($estaSemana, function ($a, $b) { return strtotime($a['fecha_pago']) - strtotime($b['fecha_pago']); });
        usort($proximos, function ($a, $b) { return strtotime($a['fecha_pago']) - strtotime($b['fecha_pago']); });

        return response()->json([
            'vencidos' => $vencidos,
            'esta_semana' => $estaSemana,
            'proximos' => $proximos,
        ]);
    }
}
