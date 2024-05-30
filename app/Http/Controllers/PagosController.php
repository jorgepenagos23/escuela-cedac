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
        $pendienteMesUser = Inscripcion::leftJoin("pagos_colegiatura", "alumno_inscripcion.id", "=", "pagos_colegiatura.alumno_id")
            ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
            ->whereNull("pagos_colegiatura.alumno_id")
            ->select('alumno_inscripcion.id as alumno_id',
            'alumno_inscripcion.nombre_alumno', 'alumno_inscripcion.fecha_inscripcion',
            'alumno_inscripcion.saldo',
            'alumno_inscripcion.celular',
            'alumno_inscripcion.adicional',
            'alumno_inscripcion.fecha_inscripcion',
            'alumno_inscripcion.monto_inscripcion',
             'diplomados.nombre as nombre_diplomado',
             'alumno_inscripcion.diplomado_id as diplomado_id',
             'diplomados.id as id_diplomado',

             )
            ->get();

        // Eliminar el campo original 'id' ya que ahora tenemos 'alumno_id'
        $pendienteMesUser->transform(function ($item) {
            unset($item->id);
            return $item;
        });

        return response()->json([
            'pendienteMesUser' => $pendienteMesUser
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
            $pago->tutor = $request ->input('tutor');

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
            'alumno_inscripcion.created_at as created_at',
            'alumno_inscripcion.updated_at as updated_at'
            // Agregar los campos adicionales aquí
        )
        ->join('alumno_inscripcion', 'alumno_inscripcion.id', '=', 'pagos_colegiatura.alumno_id')
        ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
        ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor')
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->join('grupo_campañas', 'grupo_campañas.id', '=', 'alumno_inscripcion.grupo_campa')
        ->join('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
   //     ->where('alumno_inscripcion.saldo', '>', 0)
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
            'tutores.name',
            'alumno_inscripcion.created_at',
            'alumno_inscripcion.updated_at'
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
