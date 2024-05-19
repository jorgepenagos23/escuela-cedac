<?php

namespace App\Http\Controllers;

use App\Models\CuentadeDeposito;
use Illuminate\Http\Request;

class CuentadePagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index_cuenta(){
        $cuentaDeposito = CuentadeDeposito::all()->map(function ($cuentaDeposito) {
            // Concatenar el nombre del titular y el número de cuenta
            $titularConCuenta = $cuentaDeposito->titular . ' - ' . $cuentaDeposito->numero_cuenta;


            $titularConCuentaCLABE = '';
            // Verificar si la CLABE está presente antes de concatenarla
            if (!empty($cuentaDeposito->CLABE)) {
                $titularConCuentaCLABE =  $cuentaDeposito->CLABE;
            }

            return [
                'id' => $cuentaDeposito->id,
                'titular' => $titularConCuenta,
                'CLABE' => $titularConCuentaCLABE,
            ];
        });

        return response()->json([
            'cuentaDeposito' => $cuentaDeposito
        ]);
    }




    public function index()
    {
        $cuentadeposito = CuentadeDeposito::all();
        //$cuentadeposito =CuentadeDeposito::orderBy("created_at","desc")->paginate(10);

        return response()->json([
            "data" => $cuentadeposito,
            "message" =>  "Consulta Exitosa"
            ,200
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
