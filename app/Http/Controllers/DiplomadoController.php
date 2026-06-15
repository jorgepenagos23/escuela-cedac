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
                 "id"          => $grupo->id,
                 'campaña'     => $grupo->campaña,
                 'diplomado_id'=> $grupo->id_diplomado,
                 'tutor_id'    => $grupo->tutor_id,
                 'costo_total' => $grupo->diplomado->costo_total ?? 0,
                 'duracion_mes'=> $grupo->diplomado->duracion_mes ?? 5,
                 'nombre'      => $grupo->diplomado->nombre ?? 'No definido',
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
        $diplomados = Diplomado::with(['grupoCampañas.tutor'])->get()->map(function ($d) {
            $gc = $d->grupoCampañas->first();
            $d->campaña = $gc ? $gc->campaña : '';
            $d->grupo = $gc ? $gc->grupo : '';
            $d->tutor_id = $gc ? $gc->tutor_id : null;
            $d->tutor_nombre = ($gc && $gc->tutor) ? $gc->tutor->name : 'Sin asignar';
            return $d;
        });

        return response()->json([
            'diplomados' => $diplomados,
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
        if ($request->has('duracion_mes')) {
             $soloNumeros = preg_replace('/[^0-9]/', '', $request->duracion_mes);
             $request->merge(['duracion_mes' => (int)$soloNumeros]);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'duracion_mes' => 'required|integer|min:1',
            'costo_total' => 'required|numeric|min:0',
            'requisitos' => 'nullable|string',
            'campaña' => 'nullable|string',
            'grupo' => 'nullable|string',
            'tutor_id' => 'nullable|integer|exists:users,id',
        ]);

        $diplomado = Diplomado::create([
            'nombre' => $request->nombre,
            'duracion_mes' => $request->duracion_mes,
            'costo_total' => $request->costo_total,
            'requisitos' => $request->requisitos,
        ]);

        if ($request->filled('campaña') || $request->filled('grupo') || $request->filled('tutor_id')) {
            GrupoCampaña::create([
                'campaña' => $request->input('campaña') ?: '2025',
                'grupo' => $request->input('grupo') ?: 'A',
                'id_diplomado' => $diplomado->id,
                'tutor_id' => $request->tutor_id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Diplomado y Campaña Creados con Éxito',
            'diplomado' => $diplomado,
        ], 201);
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
    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info('Actualizando Diplomado:', $request->all());

        if ($request->has('tutor_id') && $request->tutor_id === "") {
             $request->merge(['tutor_id' => null]);
        }

        if ($request->has('costo_total')) {
             $costo = str_replace([',', '$'], '', $request->costo_total);
             $request->merge(['costo_total' => $costo]);
        }

        if ($request->has('duracion_mes')) {
             $soloNumeros = preg_replace('/[^0-9]/', '', $request->duracion_mes);
             $request->merge(['duracion_mes' => (int)$soloNumeros]);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'duracion_mes' => 'required|integer|min:1',
            'costo_total' => 'required|numeric|min:0',
            'requisitos' => 'nullable|string',
            'campaña' => 'nullable|string',
            'grupo' => 'nullable|string',
            'tutor_id' => 'nullable|integer|exists:users,id',
        ]);

        $diplomado = Diplomado::findOrFail($id);
        $diplomado->update([
            'nombre' => $request->nombre,
            'duracion_mes' => $request->duracion_mes,
            'costo_total' => $request->costo_total,
            'requisitos' => $request->requisitos,
        ]);

        if ($request->filled('campaña') || $request->filled('grupo') || $request->filled('tutor_id')) {
            GrupoCampaña::updateOrCreate(
                ['id_diplomado' => $diplomado->id],
                [
                    'campaña' => $request->input('campaña') ?: '2025', 
                    'grupo' => $request->input('grupo') ?: 'A',
                    'tutor_id' => $request->tutor_id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Diplomado actualizado con éxito',
            'diplomado' => $diplomado,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Diplomado $diplomado)
    {
        //
    }
}
