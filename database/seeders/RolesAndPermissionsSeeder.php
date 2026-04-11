<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Limpiar caché de permisos de spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─────────────────────────────────────────
        // 1. DEFINIR TODOS LOS PERMISOS DEL SISTEMA
        // ─────────────────────────────────────────
        $permisos = [
            // Datos Maestros
            ['name' => 'ver_datos_maestros',         'grupo' => 'Datos Maestros'],
            ['name' => 'actualizar_datos_maestros',   'grupo' => 'Datos Maestros'],
            ['name' => 'crear_diplomados',            'grupo' => 'Datos Maestros'],
            ['name' => 'eliminar_diplomados',         'grupo' => 'Datos Maestros'],

            // Alumnos / Inscripciones
            ['name' => 'ver_alumnos',                'grupo' => 'Alumnos'],
            ['name' => 'crear_alumnos',              'grupo' => 'Alumnos'],
            ['name' => 'editar_alumnos',             'grupo' => 'Alumnos'],
            ['name' => 'eliminar_alumnos',           'grupo' => 'Alumnos'],
            ['name' => 'ver_inscripciones',          'grupo' => 'Alumnos'],
            ['name' => 'crear_inscripciones',        'grupo' => 'Alumnos'],

            // Pagos / Abonos
            ['name' => 'ver_pagos',                  'grupo' => 'Pagos'],
            ['name' => 'registrar_pagos_nuevos',     'grupo' => 'Pagos'],
            ['name' => 'editar_pagos_manual',        'grupo' => 'Pagos'],
            ['name' => 'cancelar_abono',             'grupo' => 'Pagos'],
            ['name' => 'agregar_descuento',          'grupo' => 'Pagos'],
            ['name' => 'ver_contabilidad',           'grupo' => 'Pagos'],
            ['name' => 'exportar_reportes',          'grupo' => 'Pagos'],

            // Tutoría
            ['name' => 'ver_seguimiento_tutoria',    'grupo' => 'Tutoría'],
            ['name' => 'editar_seguimiento_tutoria', 'grupo' => 'Tutoría'],
            ['name' => 'ver_alumnos_liquidados',     'grupo' => 'Tutoría'],

            // Admisiones
            ['name' => 'ver_seguimiento_admisiones', 'grupo' => 'Admisiones'],
            ['name' => 'gestionar_admisiones',       'grupo' => 'Admisiones'],

            // Administración
            ['name' => 'gestionar_usuarios',         'grupo' => 'Administración'],
            ['name' => 'gestionar_roles_permisos',   'grupo' => 'Administración'],
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(
                ['name' => $permiso['name']],
                ['guard_name' => 'web']
            );
        }

        // ─────────────────────────────────────────
        // 2. CREAR ROLES Y ASIGNAR PERMISOS
        // ─────────────────────────────────────────

        // Rol TI (Super Admin — acceso total)
        $roleTi = Role::firstOrCreate(['name' => 'TI']);
        $roleTi->syncPermissions(Permission::all());

        // Rol Administrador
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->syncPermissions([
            'ver_datos_maestros',
            'actualizar_datos_maestros',
            'crear_diplomados',
            'ver_alumnos',
            'crear_alumnos',
            'editar_alumnos',
            'ver_inscripciones',
            'crear_inscripciones',
            'ver_pagos',
            'registrar_pagos_nuevos',
            'editar_pagos_manual',
            'cancelar_abono',
            'agregar_descuento',
            'ver_contabilidad',
            'exportar_reportes',
            'ver_seguimiento_tutoria',
            'ver_alumnos_liquidados',
            'ver_seguimiento_admisiones',
            'gestionar_admisiones',
            'gestionar_usuarios',
        ]);

        // Rol Tutoría
        $roleTutoria = Role::firstOrCreate(['name' => 'Tutoria']);
        $roleTutoria->syncPermissions([
            'ver_alumnos',
            'ver_inscripciones',
            'ver_pagos',
            'registrar_pagos_nuevos',
            'ver_seguimiento_tutoria',
            'editar_seguimiento_tutoria',
            'ver_alumnos_liquidados',
        ]);

        // Rol Admisiones
        $roleAdmisiones = Role::firstOrCreate(['name' => 'Admisiones']);
        $roleAdmisiones->syncPermissions([
            'ver_alumnos',
            'crear_alumnos',
            'editar_alumnos',
            'ver_inscripciones',
            'crear_inscripciones',
            'ver_pagos',
            'registrar_pagos_nuevos',
            'ver_seguimiento_admisiones',
            'gestionar_admisiones',
        ]);

        // Rol Tutor (legacy)
        $roleTutor = Role::firstOrCreate(['name' => 'Tutor']);
        $roleTutor->syncPermissions([
            'ver_alumnos',
            'ver_inscripciones',
            'ver_pagos',
            'ver_seguimiento_tutoria',
        ]);

        // Rol Alumno
        $roleAlumno = Role::firstOrCreate(['name' => 'Alumno']);
        $roleAlumno->syncPermissions(['ver_pagos']);
    }
}
