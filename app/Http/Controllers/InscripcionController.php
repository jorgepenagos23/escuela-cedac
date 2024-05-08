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

            'pago_inscripcion.*',
             'alumnos.nombre_completo as alumno_nombre',
            'diplomados.nombre as nombre_diplomado',
            'cuenta_deposito.titular  as Titular',
            'cuenta_deposito.banco as banco',
            'cuenta_deposito.numero_cuenta as numero_cuenta'
            )


        ->join('alumnos','alumnos.id','=','pago_inscripcion.alumno_id')
        ->join('diplomados','alumnos.id_diplomado','=','diplomados.id')
        ->join('cuenta_deposito','pago_inscripcion.cuentadeposito','=','cuenta_deposito.id')
        ->get();



        return response()->json([
            'alumnos_inscripcion'=> $AlumnosConInscripciones,
            'mesagge'=>'Exito en consulta ',
            'code'=> 200,
        ]);

    }

    public function sumaIncripciones()
    {
        $sumaIncripciones = Inscripcion::select(
            'diplomados.id as id_diplomado',
            'diplomados.nombre as nombre_diplomado',
            DB::raw('COUNT(pago_inscripcion.id) as TotalInscritos'), // Contar el número de inscritos
            DB::raw('GROUP_CONCAT(pago_inscripcion.fecha_inscripcion) as FechaInscrito'), // Concatenar fechas de inscripción
            DB::raw('SUM(pago_inscripcion.monto_inscripcion) as TotalInscripcion') // Sumar el monto de inscripción
        )
        ->join('alumnos', 'alumnos.id', '=', 'pago_inscripcion.alumno_id')
        ->join('diplomados', 'diplomados.id', '=', 'pago_inscripcion.diplomado_id')
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

            $inscripcion->fecha_inscripcion = $request->input('fecha_inscripcion');
            $inscripcion->descripcion = $request->input('descripcion');
            $inscripcion->cuentadeposito = $request ->input('cuentadeposito');
            $inscripcion->monto_inscripcion = $request ->input('monto_inscripcion');
            $inscripcion->diplomado_id = $request ->input('diplomado_id');
            $inscripcion->alumno_id = $request ->input('alumno_id');

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
