<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Illuminate\Http\Request;
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
        //
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
