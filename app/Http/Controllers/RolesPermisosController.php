<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosController extends Controller
{
    /**
     * Los roles que son configurables desde la vista (TI siempre tiene todo).
     */
    private const ROLES_CONFIGURABLES = ['Administrador', 'Tutoria', 'Admisiones', 'Tutor', 'Alumno'];

    /**
     * Agrupación visual de permisos para la UI
     */
    private const GRUPOS_PERMISOS = [
        'Datos Maestros'  => ['ver_datos_maestros', 'actualizar_datos_maestros', 'crear_diplomados', 'eliminar_diplomados'],
        'Alumnos'         => ['ver_alumnos', 'crear_alumnos', 'editar_alumnos', 'eliminar_alumnos', 'ver_inscripciones', 'crear_inscripciones'],
        'Pagos'           => ['ver_pagos', 'registrar_pagos_nuevos', 'editar_pagos_manual', 'cancelar_abono', 'agregar_descuento', 'ver_contabilidad', 'exportar_reportes'],
        'Tutoría'         => ['ver_seguimiento_tutoria', 'editar_seguimiento_tutoria', 'ver_alumnos_liquidados'],
        'Admisiones'      => ['ver_seguimiento_admisiones', 'gestionar_admisiones'],
        'Administración'  => ['gestionar_usuarios', 'gestionar_roles_permisos'],
    ];

    /**
     * Etiquetas legibles para cada permiso
     */
    private const ETIQUETAS = [
        'ver_datos_maestros'          => 'Ver datos maestros',
        'actualizar_datos_maestros'   => 'Actualizar datos maestros',
        'crear_diplomados'            => 'Crear diplomados',
        'eliminar_diplomados'         => 'Eliminar diplomados',
        'ver_alumnos'                 => 'Ver alumnos',
        'crear_alumnos'               => 'Registrar alumnos',
        'editar_alumnos'              => 'Editar alumnos',
        'eliminar_alumnos'            => 'Eliminar alumnos',
        'ver_inscripciones'           => 'Ver inscripciones',
        'crear_inscripciones'         => 'Crear inscripciones',
        'ver_pagos'                   => 'Ver pagos',
        'registrar_pagos_nuevos'      => 'Registrar abonos',
        'editar_pagos_manual'         => 'Editar pagos manualmente',
        'cancelar_abono'              => 'Cancelar un abono',
        'agregar_descuento'           => 'Aplicar descuentos',
        'ver_contabilidad'            => 'Ver contabilidad',
        'exportar_reportes'           => 'Exportar reportes',
        'ver_seguimiento_tutoria'     => 'Ver seguimiento tutoría',
        'editar_seguimiento_tutoria'  => 'Editar seguimiento tutoría',
        'ver_alumnos_liquidados'      => 'Ver alumnos liquidados',
        'ver_seguimiento_admisiones'  => 'Ver seguimiento admisiones',
        'gestionar_admisiones'        => 'Gestionar admisiones',
        'gestionar_usuarios'          => 'Gestionar usuarios del sistema',
        'gestionar_roles_permisos'    => 'Gestionar roles y permisos',
    ];

    /**
     * Vista principal de administración de roles y permisos
     */
    public function index()
    {
        // Todos los permisos agrupados con su etiqueta
        $gruposPermisos = [];
        foreach (self::GRUPOS_PERMISOS as $grupo => $permisosGrupo) {
            $items = [];
            foreach ($permisosGrupo as $nombre) {
                $items[] = [
                    'name'     => $nombre,
                    'etiqueta' => self::ETIQUETAS[$nombre] ?? $nombre,
                ];
            }
            $gruposPermisos[] = [
                'grupo'    => $grupo,
                'permisos' => $items,
            ];
        }

        // Roles configurables con sus permisos actuales
        $roles = Role::whereIn('name', self::ROLES_CONFIGURABLES)
            ->with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                ];
            });

        return Inertia::render('RolesPermisos/Index', [
            'rolesData'      => $roles,
            'gruposPermisos' => $gruposPermisos,
        ]);
    }

    /**
     * Sincroniza los permisos de un rol específico
     */
    public function update(Request $request, $roleId)
    {
        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($roleId);

        // Seguridad: no se puede modificar el rol TI desde la UI
        if ($role->name === 'TI') {
            return back()->with('error', 'El rol TI no puede ser modificado desde la interfaz.');
        }

        // Solo se pueden asignar permisos de los grupos configurables
        $permisosPermitidos = collect(self::GRUPOS_PERMISOS)->flatten()->toArray();
        $permisosValidos = array_intersect($request->permissions, $permisosPermitidos);

        $role->syncPermissions($permisosValidos);

        // Limpiar caché de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', "Permisos del rol \"{$role->name}\" actualizados correctamente.");
    }
}
