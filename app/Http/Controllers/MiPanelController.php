<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\Auth;

class MiPanelController extends Controller
{
    public function indexView()
    {
        return Inertia::render('MiPanel');
    }

    public function getData()
    {
        $user = Auth::user();
        $roles = $user->getRoleNames(); // Spatie permissions
        $userId = $user->id;

        // Mis inscritos, aquellos en los que fui asesor o tutor
        $queryInscripciones = Inscripcion::with(['diplomado', 'pagos'])
            ->where(function ($query) use ($userId) {
                $query->where('asesor', $userId)
                      ->orWhere('tutor', $userId);
            });

        $totalAlumnos = $queryInscripciones->count();
        $inscripcionesGenerales = $queryInscripciones->orderBy('fecha_inscripcion', 'desc')->get();

        $sinColegiaturas = [];
        // Lo que este operario ha cobrado (tabla de pagos)
        $miCobranza = \App\Models\Pagos::where('tutor', $userId)->sum('pago_colegiatura');

        foreach ($inscripcionesGenerales as $inscripcion) {
            // Verificar si el alumno no ha realizado NINGUNA colegiatura (Solo pagó inscripción general)
            if ($inscripcion->pagos->count() === 0) {
                $sinColegiaturas[] = [
                    'id' => $inscripcion->id,
                    'nombre_alumno' => $inscripcion->nombre_alumno,
                    'diplomado' => $inscripcion->diplomado->nombre ?? 'N/A',
                    'fecha_inscripcion' => $inscripcion->fecha_inscripcion,
                    'celular' => $inscripcion->celular,
                    'monto_inscripcion' => $inscripcion->monto_inscripcion,
                ];
            }
        }

        return response()->json([
            'usuario' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
            ],
            'kpis' => [
                'total_inscritos' => $totalAlumnos,
                'alumnos_sin_pagos' => count($sinColegiaturas),
                'total_cobranza_generada' => $miCobranza
            ],
            'lista_sin_pagos' => $sinColegiaturas
        ]);
    }
}
