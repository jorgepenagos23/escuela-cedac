<?php

namespace App\Http\Controllers;

use App\Models\Diplomado;
use App\Models\GrupoCampaña;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiplomadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function diplomadosCrud(){

        return Inertia::render('AgregarDiplomados');

    }


     public function sumaDiplomados(){




     }


     public function listarGrupos(Request $request)
     {
         // Load groups with the associated diplomado using eager loading
         $grupos = GrupoCampaña::with('diplomado')->get()->map(function($grupo) {
             return [
                 "id" => $grupo->id,
                 'campaña' => $grupo->campaña,
                 'grupo' => $grupo->grupo,
                 'diplomado_id' => $grupo->id_diplomado,
                 'nombre' => $grupo->diplomado->nombre ?? 'No definido', // Assuming the column is 'nombre'
             ];
         });

         return response()->json([
             'Grupos' => $grupos,
         ]);
     }



 public function index_diplomados()
{
    $diplomados = Diplomado::all()->map(function ($diplomado) {
        return [
            'id' => $diplomado->id,
            'nombre' => $diplomado->nombre,
        ];
    });

    return response()->json([
        'DiplomadoNombre' => $diplomados
    ]);
}



    public function index()
    {
        $diplomado = Diplomado::all();
        return response()->json([
            'diplomados' => $diplomado,
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
