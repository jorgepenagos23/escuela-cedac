<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InscripcionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function seguimiento_inscripciones(){

        return Inertia::render('Inscripciones_Seguimiento');
    }



     public function inscripciones(){

        return Inertia::render("Inscripciones");
    }

    public function index()
    {
        $AlumnosConInscripciones = Inscripcion::select(
            'alumno_inscripcion.*',
            'diplomados.nombre as nombre_diplomado',
            'cuenta_deposito.titular  as Titular',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.numero_cuenta as numero_cuenta',
            'cuenta_deposito.CLABE as CLABE',
            'cuenta_deposito.banco as Banco',

            'users.name as Tutor',
            'tutores.name as Asesor' // Añade esta línea para obtener el nombre de otro usuario
        )
        ->join('users', 'users.id', '=', 'alumno_inscripcion.tutor')
        ->join('users as tutores', 'tutores.id', '=', 'alumno_inscripcion.asesor') // Cambia 'otro_usuario_id' por el nombre correcto de la columna
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->join('cuenta_deposito', 'alumno_inscripcion.cuentadeposito', '=', 'cuenta_deposito.id')
        ->where('alumno_inscripcion.saldo','>',0)
        ->get();

        return response()->json([
            'alumnos_inscripcion'=> $AlumnosConInscripciones,
            'mesagge'=>'Exito en consulta ',
            'code'=> 200,
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
            DB::raw('COUNT(alumno_inscripcion.id) as TotalInscritos'), // Contar el número de inscritos
            DB::raw('GROUP_CONCAT(alumno_inscripcion.fecha_inscripcion) as FechaInscrito'), // Concatenar fechas de inscripción
            DB::raw('SUM(alumno_inscripcion.monto_inscripcion) as TotalInscripcion') // Sumar el monto de inscripción
        )
        ->join('diplomados', 'diplomados.id', '=', 'alumno_inscripcion.diplomado_id')
        ->orderByDesc('TotalInscripcion')
        ->groupBy('diplomados.id', 'diplomados.nombre')
        ->get();

        return response()->json([
            'sumaIncripciones' => $sumaIncripciones
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
            $inscripcion->save();


            return response()->json([
                 'message'=> 'inscripcion agregado exisotamente ',
                 'inscripcion' => $inscripcion

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
