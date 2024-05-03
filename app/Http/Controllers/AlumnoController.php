<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlumnoIndexRequest;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function alumno()
    {

        return Inertia::render("Alumnos");
    }
    public function index(Request $request)
    {
        $alumnosConDiplomados = Alumno::select('alumnos.*', 'diplomados.nombre as nombre_diplomado')
            ->join('diplomados', 'alumnos.id_diplomado', '=', 'diplomados.id')->get();

        return response()->json([
            'alumnos' => $alumnosConDiplomados

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
            
                    'nombre_completo' => 'required|string|max:255',
                    'matricula' => 'required|string|max:255',
                    'fecha_nacimiento'=> 'required|date',
                    'correo'=> 'required|string|max:255',
                    'telefono'=> 'required|string|max:255',
                    'direccion'=> 'required|string|max:255',
                    'id_diplomado'=> 'required|integer',

                ];
                
                $request->validate($rules);

                $alumno = new Alumno();
                $alumno->nombre_completo = $request->input('nombre_completo');
                $alumno->matricula = $request->input('matricula');  
                $alumno->fecha_nacimiento = $request->input('fecha_nacimiento');
                $alumno->correo = $request->input('correo'); 
                $alumno->direccion = $request->input('direccion');  
                $alumno->telefono = $request->input('telefono');
                $alumno->id_diplomado =$request->input('id_diplomado');


                $alumno->save();

                return response()->json(['message' => '¡Alumno creado exitosamente!']);

            }



            public function crudalumnos(){


                return Inertia::render('AgregarAlumnos');
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
