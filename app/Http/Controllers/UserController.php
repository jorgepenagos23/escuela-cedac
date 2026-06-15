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

    /**
     * Crea un nuevo usuario en el sistema con un rol inicial.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name'
        ], [
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->back()->with('success', 'Usuario creado y configurado correctamente.');
    }
}
