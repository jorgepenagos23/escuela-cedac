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

            // Recalcular plan_pagos
            if ($inscripcion->plan_pagos) {
                $plan = is_string($inscripcion->plan_pagos) ? json_decode($inscripcion->plan_pagos, true) : $inscripcion->plan_pagos;
                if (is_array($plan)) {
                    // Total pagado activo actual (excluyendo el abono cancelado)
                    $totalPagadoActivo = Pagos::where('alumno_id', $inscripcion->id)
                                              ->where('status', 'Activo')
                                              ->sum('pago_colegiatura');

                    $montoAbono = (float) $totalPagadoActivo;
                    foreach ($plan as &$cuota) {
                        $cuota['abonado'] = 0;
                        $cuota['estado'] = 'pendiente';

                        if ($montoAbono > 0) {
                            $montoCuota = (float) ($cuota['monto'] ?? 0);
                            
                            if ($montoAbono >= $montoCuota) {
                                $cuota['abonado'] = $montoCuota;
                                $cuota['estado'] = 'pagado';
                                $montoAbono -= $montoCuota;
                            } else {
                                $cuota['abonado'] = $montoAbono;
                                $cuota['estado'] = 'parcial';
                                $montoAbono = 0;
                            }
                        }
                    }

                    $inscripcion->plan_pagos = json_encode($plan);
                    $inscripcion->save();
                }
            }

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
        
        $nuevoPlan = $request->plan;
        $totalPagadoActivo = Pagos::where('alumno_id', $inscripcion->id)
                                  ->where('status', 'Activo')
                                  ->sum('pago_colegiatura');

        $montoAbono = (float) $totalPagadoActivo;
        foreach ($nuevoPlan as &$cuota) {
            $cuota['abonado'] = 0;
            $cuota['estado'] = 'pendiente';

            if ($montoAbono > 0) {
                $montoCuota = (float) ($cuota['monto'] ?? 0);
                if ($montoAbono >= $montoCuota) {
                    $cuota['abonado'] = $montoCuota;
                    $cuota['estado'] = 'pagado';
                    $montoAbono -= $montoCuota;
                } else {
                    $cuota['abonado'] = $montoAbono;
                    $cuota['estado'] = 'parcial';
                    $montoAbono = 0;
                }
            }
        }

        $inscripcion->plan_pagos = json_encode($nuevoPlan);
        $inscripcion->save();

        return response()->json([
            'message'  => 'Plan de pagos actualizado correctamente.',
            'plan'     => $nuevoPlan,
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

    // ─── Vista Inertia del reporte financiero ────────────────────────────────
    public function vistaFinanciero()
    {
        return Inertia::render('ReporteFinanciero');
    }

    // ─── API: Dashboard financiero del director ───────────────────────────────
    public function reporteFinancieroDashboard()
    {
        $hoy          = Carbon::now();
        $inicioMes    = $hoy->copy()->startOfMonth();
        $finMes       = $hoy->copy()->endOfMonth();
        $inicioSemana = $hoy->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana    = $hoy->copy()->endOfWeek(Carbon::SUNDAY);
        $inicio30     = $hoy->copy()->subDays(29)->startOfDay();

        // ── Colegiaturas (hoy / semana / mes) ─────────────────────────────
        $colHoy    = (float) Pagos::where('status', 'Activo')
            ->whereDate('Fecha_PrimerContacto', $hoy->toDateString())
            ->sum('pago_colegiatura');

        $colSemana = (float) Pagos::where('status', 'Activo')
            ->whereBetween('Fecha_PrimerContacto', [$inicioSemana, $finSemana])
            ->sum('pago_colegiatura');

        $colMes    = (float) Pagos::where('status', 'Activo')
            ->whereBetween('Fecha_PrimerContacto', [$inicioMes, $finMes])
            ->sum('pago_colegiatura');

        // ── Inscripciones (hoy / semana / mes) ────────────────────────────
        $insHoy    = (float) Inscripcion::whereDate('fecha_inscripcion', $hoy->toDateString())
            ->sum('monto_inscripcion');

        $insSemana = (float) Inscripcion::whereBetween('fecha_inscripcion', [$inicioSemana, $finSemana])
            ->sum('monto_inscripcion');

        $insMes    = (float) Inscripcion::whereBetween('fecha_inscripcion', [$inicioMes, $finMes])
            ->sum('monto_inscripcion');

        // ── Ingresos diarios: últimos 30 días ─────────────────────────────
        $colDiarios = Pagos::selectRaw("DATE(Fecha_PrimerContacto) as dia, SUM(pago_colegiatura) as total")
            ->where('status', 'Activo')
            ->where('Fecha_PrimerContacto', '>=', $inicio30->toDateString())
            ->groupByRaw("DATE(Fecha_PrimerContacto)")
            ->pluck('total', 'dia');

        $insDiarios = Inscripcion::selectRaw("DATE(fecha_inscripcion) as dia, SUM(monto_inscripcion) as total")
            ->where('fecha_inscripcion', '>=', $inicio30->toDateString())
            ->groupByRaw("DATE(fecha_inscripcion)")
            ->pluck('total', 'dia');

        $ingresosDiarios = [];
        for ($i = 29; $i >= 0; $i--) {
            $f = $hoy->copy()->subDays($i)->format('Y-m-d');
            $c = (float)($colDiarios[$f] ?? 0);
            $n = (float)($insDiarios[$f] ?? 0);
            $ingresosDiarios[] = ['fecha' => $f, 'colegiaturas' => $c, 'inscripciones' => $n, 'total' => $c + $n];
        }

        // ── Desglose por diplomado (mes actual) ───────────────────────────
        $colDip = Pagos::selectRaw("pagos_colegiatura.diplomado_id, diplomados.nombre as diplomado, SUM(pagos_colegiatura.pago_colegiatura) as col, COUNT(*) as npagos")
            ->join('diplomados', 'diplomados.id', '=', 'pagos_colegiatura.diplomado_id')
            ->where('pagos_colegiatura.status', 'Activo')
            ->whereBetween('pagos_colegiatura.Fecha_PrimerContacto', [$inicioMes, $finMes])
            ->groupBy('pagos_colegiatura.diplomado_id', 'diplomados.nombre')
            ->get()->keyBy('diplomado_id');

        $insDip = Inscripcion::selectRaw("alumno_inscripcion.diplomado_id, diplomados.nombre as diplomado, SUM(alumno_inscripcion.monto_inscripcion) as ins, COUNT(*) as nins")
            ->join('diplomados', 'diplomados.id', '=', 'alumno_inscripcion.diplomado_id')
            ->whereBetween('alumno_inscripcion.fecha_inscripcion', [$inicioMes, $finMes])
            ->groupBy('alumno_inscripcion.diplomado_id', 'diplomados.nombre')
            ->get()->keyBy('diplomado_id');

        $dipIds = collect(
            array_merge($colDip->keys()->toArray(), $insDip->keys()->toArray())
        )->unique();

        $porDiplomado = $dipIds->map(function ($id) use ($colDip, $insDip) {
            $c = $colDip->get($id);
            $n = $insDip->get($id);
            return [
                'diplomado_id'  => $id,
                'diplomado'     => $c ? $c->diplomado : ($n ? $n->diplomado : '—'),
                'colegiaturas'  => (float)($c->col  ?? 0),
                'inscripciones' => (float)($n->ins  ?? 0),
                'num_pagos'     => (int)($c->npagos ?? 0),
                'num_inscritos' => (int)($n->nins   ?? 0),
                'total'         => (float)($c->col ?? 0) + (float)($n->ins ?? 0),
            ];
        })->sortByDesc('total')->values();

        // ── Proyección cierre de mes ──────────────────────────────────────
        // Suma de colegiaturas esperadas (cartera cuya próxima fecha cae en el resto del mes)
        $todasIns = Inscripcion::with(['pagos' => fn($q) => $q->where('status', 'Activo')])
            ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->select('alumno_inscripcion.*', 'diplomados.costo_total as costo_diplomado')
            ->get();

        $proyectadoPendiente = 0.0;
        foreach ($todasIns as $a) {
            $pagado = $a->pagos->sum('pago_colegiatura');
            $saldo  = $a->costo_diplomado - ($a->monto_inscripcion + $pagado);
            if ($saldo <= 0) continue;
            $cantPagos = $a->pagos->count();
            $fechaBase = $a->fecha_primer_pago_colegiatura ?? $a->created_at;
            $proxFecha = Carbon::parse($fechaBase)->addMonths($cantPagos)->startOfDay();
            if ($proxFecha->gt($hoy) && $proxFecha->lte($finMes)) {
                // Estima cuota promedio o 25% del saldo si no hay historial
                $cuotaEstimada = $cantPagos > 0 ? ($pagado / $cantPagos) : ($saldo * 0.25);
                $proyectadoPendiente += min($saldo, $cuotaEstimada);
            }
        }

        // ── Detalle de colegiaturas HOY ───────────────────────────────────
        $detalleHoy = Pagos::select(
                'pagos_colegiatura.id',
                'pagos_colegiatura.pago_colegiatura as monto',
                'pagos_colegiatura.Fecha_PrimerContacto as fecha_op',
                'pagos_colegiatura.created_at as registrado',
                'alumno_inscripcion.nombre_alumno',
                'diplomados.nombre as diplomado',
                'cuenta_deposito.banco',
                'cuenta_deposito.titular',
                'users.name as cajero'
            )
            ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
            ->join('diplomados', 'diplomados.id', '=', 'pagos_colegiatura.diplomado_id')
            ->join('cuenta_deposito', 'cuenta_deposito.id', '=', 'pagos_colegiatura.cuentadeposito')
            ->join('users', 'users.id', '=', 'pagos_colegiatura.tutor')
            ->where('pagos_colegiatura.status', 'Activo')
            ->whereDate('pagos_colegiatura.Fecha_PrimerContacto', $hoy->toDateString())
            ->orderByDesc('pagos_colegiatura.created_at')
            ->get();

        // ── Inscripciones de HOY ──────────────────────────────────────────
        $inscripcionesHoy = Inscripcion::select(
                'alumno_inscripcion.id',
                'alumno_inscripcion.nombre_alumno',
                'alumno_inscripcion.monto_inscripcion as monto',
                'alumno_inscripcion.fecha_inscripcion',
                'diplomados.nombre as diplomado',
                DB::raw("COALESCE(users.name, '—') as asesor")
            )
            ->join('diplomados', 'diplomados.id', '=', 'alumno_inscripcion.diplomado_id')
            ->leftJoin('users', 'users.id', '=', 'alumno_inscripcion.asesor')
            ->whereDate('alumno_inscripcion.fecha_inscripcion', $hoy->toDateString())
            ->orderByDesc('alumno_inscripcion.created_at')
            ->get();

        // ── Resumen semanal: desglose por día (esta semana) ───────────────
        $resumenSemana = Pagos::selectRaw("DAYOFWEEK(Fecha_PrimerContacto) as dow, DATE(Fecha_PrimerContacto) as dia, SUM(pago_colegiatura) as col")
            ->where('status', 'Activo')
            ->whereBetween('Fecha_PrimerContacto', [$inicioSemana, $finSemana])
            ->groupByRaw("DAYOFWEEK(Fecha_PrimerContacto), DATE(Fecha_PrimerContacto)")
            ->pluck('col', 'dia');

        $dias = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
        $semanaDiaria = [];
        for ($d = 0; $d < 7; $d++) {
            $f = $inicioSemana->copy()->addDays($d)->format('Y-m-d');
            $semanaDiaria[] = [
                'dia'    => $dias[$d],
                'fecha'  => $f,
                'total'  => (float)($resumenSemana[$f] ?? 0),
                'hoy'    => $f === $hoy->toDateString(),
            ];
        }

        return response()->json([
            'resumen' => [
                'hoy_colegiaturas'     => $colHoy,
                'hoy_inscripciones'    => $insHoy,
                'hoy_total'            => $colHoy + $insHoy,
                'semana_colegiaturas'  => $colSemana,
                'semana_inscripciones' => $insSemana,
                'semana_total'         => $colSemana + $insSemana,
                'mes_colegiaturas'     => $colMes,
                'mes_inscripciones'    => $insMes,
                'mes_total'            => $colMes + $insMes,
                'proyeccion_cierre'    => $colMes + $insMes + $proyectadoPendiente,
                'proyectado_restante'  => $proyectadoPendiente,
                'dia_actual'           => (int) $hoy->day,
                'dias_en_mes'          => (int) $finMes->day,
            ],
            'ingresos_diarios'  => $ingresosDiarios,
            'semana_diaria'     => $semanaDiaria,
            'por_diplomado'     => $porDiplomado,
            'detalle_hoy'       => $detalleHoy,
            'inscripciones_hoy' => $inscripcionesHoy,
            'mes_nombre'        => $hoy->locale('es')->isoFormat('MMMM YYYY'),
            'semana_label'      => $inicioSemana->format('d/m') . ' – ' . $finSemana->format('d/m'),
            'generado_en'       => $hoy->toISOString(),
        ]);
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
                'id' => $alumno->id,
                'nombre_alumno' => $alumno->nombre_alumno,
                'diplomado' => $alumno->diplomado_nombre,
                'saldo' => $alumno->saldo,
                'celular' => $alumno->celular,
                'correo' => $alumno->correo,
                'fecha_pago' => $fechaProximoPago->format('Y-m-d'),
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
