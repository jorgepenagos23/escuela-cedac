<?php

namespace App\Http\Controllers;

use App\Models\Pagos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function vistaResumen(){

        return Inertia::render("Resumen");
    }


     public function vistaPagos(){
            return Inertia::render("Pagos");
     }






     public function crudPagos(){

        return Inertia::render('PagosMensualidades');
     }


    public function index()
    {
        $pagos = Pagos::select(
            'pago_abono.*',
            'diplomados.nombre as nombre_diplomado',
            'alumnos.nombre_completo as Nombre',
            'cuenta_deposito.titular  as Titular',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.numero_cuenta as numero_cuenta'

        )->join('alumnos','alumnos.id','=','pago_abono.alumno_id')
        ->join('diplomados','pago_abono.diplomado_id','=','diplomados.id')
        ->join('cuenta_deposito','pago_abono.cuentadeposito','=','cuenta_deposito.id')
        ->orderBy('pago_abono.fecha_abono','desc')
        ->get();



        return response()->json(

            [
                'PagosconMensualidades'=> $pagos
            ]);



    }


            public function sumaPagos(){

                $SumaPagos = Pagos::select(
                    "pago_abono.fecha_abono as FechaAbono",
                    "diplomados.id as id_Diplomado",
                    "diplomados.nombre as Diplomado",

                    DB::raw('SUM(pago_abono.monto_abono) as TotalPagadoAbono'),
                )
                ->join("diplomados", "diplomados.id", "=", "pago_abono.diplomado_id")
                ->groupBy( "pago_abono.fecha_abono", "diplomados.id")
                ->orderByDesc('TotalPagadoAbono')
                ->get();

            return response()->json([
                'SumaPagos' => $SumaPagos
            ]);
            }



            // ESTA CONSULTA SERIA  PARA VER EL ABONO MENSUAL POR ALUMNO EN FECHAS



            public function AlumnosAbonosTotalesporPeriodo(){
                $AlumnosPagosPendientes = Pagos::select(
                    "diplomados.id as id_Diplomado",
                    "diplomados.nombre as Diplomado",
                    "alumnos.id as idAlumno",
                    "alumnos.nombre_completo",
                    DB::raw('COUNT(pago_abono.fecha_abono) as TotalFechasAbono'), // Contar el número de fechas
                    DB::raw('GROUP_CONCAT(pago_abono.fecha_abono) as FechasAbono'),
                    DB::raw('SUM(pago_abono.monto_abono) as TotalPagadoAbono'),
                    'diplomados.costo_total', // Obtener el precio del diplomado sin raw
                    'pago_inscripcion.monto_inscripcion' // Obtener el monto de inscripción
                )
                ->join("diplomados", "diplomados.id", "=", "pago_abono.diplomado_id")
                ->join("alumnos", "alumnos.id", "=", "pago_abono.alumno_id")
                ->join("pago_inscripcion", function ($join) {
                    $join->on("pago_inscripcion.alumno_id", "=", "alumnos.id")
                         ->on("pago_inscripcion.diplomado_id", "=", "diplomados.id");
                }) // Realizar la unión con la tabla pago_inscripcion
                ->groupBy("diplomados.id", "alumnos.id", "pago_inscripcion.monto_inscripcion") // Agrupar por ID de alumno y monto de inscripción
                ->orderByDesc('TotalPagadoAbono')
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

            $pago->descripcion = $request->input('descripcion');
            $pago->fecha_abono = $request->input('fecha_abono');
            $pago->monto_abono = $request ->input('monto_abono');
            $pago->cuentadeposito = $request ->input('cuentadeposito');
            $pago->diplomado_id = $request ->input('diplomado_id');
            $pago->alumno_id = $request ->input('alumno_id');

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
}
