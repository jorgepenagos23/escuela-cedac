<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function createToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Busca el usuario por su correo electrónico
        $user = User::where('email', $request->email)->first();

        // Si no se encuentra el usuario o la contraseña es incorrecta, devuelve un error
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Crea un token para el usuario
        $token = $user->createToken('Token Name')->plainTextToken;

        // Retorna el token en la respuesta JSON
        return response()->json(['token' => $token], 200);
    }
}
