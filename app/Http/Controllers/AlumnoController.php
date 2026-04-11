<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlumnoIndexRequest;
use App\Models\Alumno;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdmissionsImport;
use App\Imports\HistoricalPaymentsImport;

class AlumnoController extends Controller
{
    public function importAdmissions(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new AdmissionsImport, $request->file('file'));
            return back()->with('success', 'Admisiones importadas correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error en Admisiones: ' . $e->getMessage());
        }
    }

    public function importHistorical(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new HistoricalPaymentsImport, $request->file('file'));
            return back()->with('success', 'Histórico de pagos importado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error en Histórico: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     */


     public function index_alumnos(){

        $Alumnos = Alumno::select([
            "nombre_completo",
            "id",
        ])->get();


        return response()->json(
        [
            "Alumnos"=> $Alumnos
        ]);


     }




    public function alumno()
    {

        return Inertia::render("Alumnos");
    }
    public function index(Request $request)
    {
        // En este sistema, la base real de alumnos proviene de las Inscripciones
        $alumnosConDiplomados = \App\Models\Inscripcion::select(
            'alumno_inscripcion.id as matricula',
            'alumno_inscripcion.nombre_alumno as nombre_completo',
            'diplomados.nombre as nombre_diplomado',
            'alumno_inscripcion.fecha_inscripcion as fecha_nacimiento', // Usando de placeholder o real
            'alumno_inscripcion.celular as telefono',
            'alumno_inscripcion.adicional as correo', // En la base de datos se usa adicional por ahora
            \DB::raw("'México' as direccion") // Placeholder si no hay en base
        )
        ->join('diplomados', 'alumno_inscripcion.diplomado_id', '=', 'diplomados.id')
        ->orderBy('alumno_inscripcion.created_at', 'desc')
        ->get();

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
