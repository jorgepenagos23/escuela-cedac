<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Usuarios;
use Illuminate\Http\Request;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */


     public function listarAsesores(){
        // Obtiene usuarios que tengan el rol Spatie 'Tutor' o 'Asesor', o que en la columna nativa tengan 'tutoria' o 'Tutor'
        $usuarios = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['Tutor', 'Asesor', 'tutoria']);
            })
            ->orWhereIn('role', ['tutoria', 'Tutor', 'Asesor'])
            ->select('id', 'name')
            ->distinct()
            ->get();

        return response()->json([
            "asesores"=> $usuarios

        ]);

     }


     public function listarUsuarios(Request $request){


     }
    public function index()
    {

        $usuarios = User::all();

        return response()->json($usuarios, 200);
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
    public function show(Usuarios $usuarios)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Usuarios $usuarios)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Usuarios $usuarios)
    {
        //
    }
}
