<?php

namespace App\Http\Controllers;

use App\Models\CuentadeDeposito;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CuentadeDepositoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cuentas = CuentadeDeposito::orderBy('id', 'desc')->get();
        return Inertia::render('CuentasBancarias', [
            'cuentas' => $cuentas
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'banco' => 'required|string|max:255',
            'titular' => 'required|string|max:255',
            'numero_cuenta' => 'nullable|string|max:50',
            'CLABE' => 'nullable|string|max:50',
        ]);

        CuentadeDeposito::create($request->all());

        return redirect()->back()->with('success', 'Cuenta bancaria creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'banco' => 'required|string|max:255',
            'titular' => 'required|string|max:255',
            'numero_cuenta' => 'nullable|string|max:50',
            'CLABE' => 'nullable|string|max:50',
        ]);

        $cuenta = CuentadeDeposito::findOrFail($id);
        $cuenta->update($request->all());

        return redirect()->back()->with('success', 'Cuenta bancaria actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cuenta = CuentadeDeposito::findOrFail($id);
        
        try {
            $cuenta->delete();
            return redirect()->back()->with('success', 'Cuenta bancaria eliminada.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar la cuenta porque ya está en uso por otros registros.']);
        }
    }
}
