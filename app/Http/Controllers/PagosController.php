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

     public function vistaPagosAgregar(){


        return Inertia::render("AgregarPagosMes");
     }




     public function crudPagos(){

        return Inertia::render('PagosMensualidades');
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

            'users.name as Tutor',
)
        ->join('diplomados','pagos_colegiatura.diplomado_id','=','diplomados.id')
        ->join('cuenta_deposito','pagos_colegiatura.cuentadeposito','=','cuenta_deposito.id')
        ->join('alumno_inscripcion','alumno_inscripcion.id','=','pagos_colegiatura.alumno_id')
        ->join('users','users.id','=','pagos_colegiatura.tutor')
        ->where('alumno_inscripcion.saldo', '>', 0)
        ->where('pagos_colegiatura.status', 'activo')
        ->orderBy('pagos_colegiatura.Fecha_PrimerContacto','asc')
        ->get();



        return response()->json(

            [
                'PagosconMensualidades'=> $pagos
            ]);



    }


            public function sumaPagos(){

                $SumaPagos = Pagos::select(
                    "pagos_colegiatura.Fecha_PrimerContacto as FechaAbono",
                    "diplomados.id as id_Diplomado",
                    "diplomados.nombre as Diplomado",

                    DB::raw('SUM(pagos_colegiatura.pago_colegiatura) as TotalPagadoAbono'),
                )
                ->join("diplomados", "diplomados.id", "=", "pagos_colegiatura.diplomado_id")
                ->groupBy( "pagos_colegiatura.Fecha_PrimerContacto", "diplomados.id")
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

            $pago->Fecha_PrimerContacto = $request->input('Fecha_PrimerContacto');
            $pago->pago_colegiatura = $request ->input('pago_colegiatura');
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

    public function directorio(Request $request) {
        $AlumnosEstadoPagar = Pagos::select(
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

                'cuenta_deposito.titular as Titular',
                'cuenta_deposito.banco as banco',
                'cuenta_deposito.numero_cuenta as numero_cuenta',
                'cuenta_deposito.CLABE as CLABE',
                'users.name as Tutor',
                'tutores.name as Asesor'
            )
            ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
            ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
            ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor')
            ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->join('grupo_campañas', 'grupo_campañas.id', '=', 'alumno_inscripcion.grupo_campa')
            ->join('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
            ->where('alumno_inscripcion.saldo', '>', 0)
            ->groupBy(
                'alumno_inscripcion.id',
                'alumno_inscripcion.nombre_alumno',
                'alumno_inscripcion.saldo',
                'alumno_inscripcion.fecha_inscripcion',
                'alumno_inscripcion.grupo_campa',
                'grupo_campañas.campaña',
                'grupo_campañas.grupo',
                'diplomados.nombre',
                'cuenta_deposito.titular',
                'cuenta_deposito.banco',
                'cuenta_deposito.numero_cuenta',
                'cuenta_deposito.CLABE',
                'users.name',
                'tutores.name'
            )
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
