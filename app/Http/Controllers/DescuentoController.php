<?php

namespace App\Http\Controllers;

use App\Models\Descuento;
use App\Models\Diplomado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DescuentoController extends Controller
{
    /** Vista principal de la lista de descuentos */
    public function index()
    {
        $descuentos = Descuento::with(['diplomado:id,nombre', 'tutor:id,name', 'creadoPor:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($d) {
                return [
                    'id'              => $d->id,
                    'nombre'          => $d->nombre,
                    'descripcion'     => $d->descripcion,
                    'tipo'            => $d->tipo,
                    'valor'           => $d->valor,
                    'etiqueta'        => $d->etiqueta,
                    'aplica_a'        => $d->aplica_a,
                    'diplomado_id'    => $d->diplomado_id,
                    'diplomado_nombre'=> $d->diplomado?->nombre,
                    'tutor_id'        => $d->tutor_id,
                    'tutor_nombre'    => $d->tutor?->name,
                    'fecha_inicio'    => $d->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin'       => $d->fecha_fin?->format('Y-m-d'),
                    'estado'          => $d->estado,
                    'vigente'         => $d->vigente,
                    'creado_por'      => $d->creadoPor?->name,
                    'created_at'      => $d->created_at?->format('d/m/Y'),
                ];
            });

        $diplomados = Diplomado::select('id', 'nombre')->orderBy('nombre')->get();
        $tutores    = User::role('Tutoria')->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Descuentos/Index', [
            'descuentos' => $descuentos,
            'diplomados' => $diplomados,
            'tutores'    => $tutores,
        ]);
    }

    /** Crea un nuevo descuento */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:120',
            'tipo'        => 'required|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric|min:0.01',
            'aplica_a'    => 'required|in:diplomado,tutor,general',
            'fecha_inicio'=> 'required|date',
            'fecha_fin'   => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'nombre.required'       => 'El nombre del descuento es obligatorio.',
            'valor.required'        => 'El valor del descuento es obligatorio.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior al inicio.',
        ]);

        Descuento::create([
            'nombre'       => $request->nombre,
            'descripcion'  => $request->descripcion,
            'tipo'         => $request->tipo,
            'valor'        => $request->valor,
            'aplica_a'     => $request->aplica_a,
            'diplomado_id' => $request->aplica_a === 'diplomado' ? $request->diplomado_id : null,
            'tutor_id'     => $request->aplica_a === 'tutor'     ? $request->tutor_id     : null,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'estado'       => 'activo',
            'creado_por'   => Auth::id(),
        ]);

        return back()->with('success', 'Descuento creado correctamente.');
    }

    /** Actualiza un descuento existente */
    public function update(Request $request, Descuento $descuento)
    {
        $request->validate([
            'nombre'      => 'required|string|max:120',
            'tipo'        => 'required|in:porcentaje,monto_fijo',
            'valor'       => 'required|numeric|min:0.01',
            'aplica_a'    => 'required|in:diplomado,tutor,general',
            'fecha_inicio'=> 'required|date',
            'fecha_fin'   => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $descuento->update([
            'nombre'       => $request->nombre,
            'descripcion'  => $request->descripcion,
            'tipo'         => $request->tipo,
            'valor'        => $request->valor,
            'aplica_a'     => $request->aplica_a,
            'diplomado_id' => $request->aplica_a === 'diplomado' ? $request->diplomado_id : null,
            'tutor_id'     => $request->aplica_a === 'tutor'     ? $request->tutor_id     : null,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
        ]);

        return back()->with('success', 'Descuento actualizado.');
    }

    /** Cambia el estado: activo / suspendido / cancelado */
    public function cambiarEstado(Request $request, Descuento $descuento)
    {
        $request->validate(['estado' => 'required|in:activo,suspendido,cancelado']);
        $descuento->update(['estado' => $request->estado]);
        return back()->with('success', 'Estado actualizado a: ' . $request->estado);
    }

    /** Elimina un descuento (solo si está cancelado) */
    public function destroy(Descuento $descuento)
    {
        if ($descuento->estado !== 'cancelado') {
            return back()->withErrors(['error' => 'Solo se pueden eliminar descuentos cancelados.']);
        }
        $descuento->delete();
        return back()->with('success', 'Descuento eliminado.');
    }

    /**
     * API: retorna los descuentos vigentes para aplicar en admisiones.
     * Filtra por diplomado_id si se provee.
     */
    public function vigentes(Request $request)
    {
        $query = Descuento::vigentes();

        if ($request->diplomado_id) {
            $query->where(function ($q) use ($request) {
                $q->where('aplica_a', 'general')
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('aplica_a', 'diplomado')
                         ->where('diplomado_id', $request->diplomado_id);
                  });
            });
        }

        if ($request->tutor_id) {
            $query->orWhere(function ($q) use ($request) {
                $q->where('aplica_a', 'tutor')
                  ->where('tutor_id', $request->tutor_id)
                  ->where('estado', 'activo');
            });
        }

        $descuentos = $query->get()->map(fn($d) => [
            'id'       => $d->id,
            'nombre'   => $d->nombre,
            'tipo'     => $d->tipo,
            'valor'    => $d->valor,
            'etiqueta' => $d->etiqueta,
            'aplica_a' => $d->aplica_a,
        ]);

        return response()->json(['descuentos' => $descuentos]);
    }
}
