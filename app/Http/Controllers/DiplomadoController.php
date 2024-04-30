<?php

namespace App\Http\Controllers;

use App\Models\Diplomado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiplomadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $diplomado = Diplomado::all();
        return response()->json([
            'data' => $diplomado,
            'code' => 200,
           'message' => 'Consulta Exitosa'

        ]);

    }


    public function Diplomado(Request $request){

        return Inertia::render('Diplomados');
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
    public function show(Diplomado $diplomado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Diplomado $diplomado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Diplomado $diplomado)
    {
        //
    }
}
