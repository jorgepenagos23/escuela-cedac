<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Devuelve la vista principal de la lista de usuarios.
     * Solo accesible por TI o usuarios con permisos específicos (gestionado vía middleware routes).
     */
    public function index()
    {
        // Traemos a los usuarios con sus roles
        $users = User::with('roles')->get();
        // Traemos todos los roles disponibles (TI, Administrador, Tutor, Alumno, etc.) para el selector
        $roles = Role::all();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Actualiza los roles de un usuario especificado por post.
     */
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        // Sincroniza el rol al usuario (quita el viejo y asigna el nuevo)
        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Rol de usuario actualizado correctamente.');
    }
}
