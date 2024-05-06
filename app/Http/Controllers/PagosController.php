<?php

namespace App\Http\Controllers;

use App\Models\Pagos;
use Illuminate\Http\Request;
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


    }



    public function AlumnosPagosPendientes(){


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
