<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Diplomado;
use App\Models\CuentadeDeposito;
use App\Models\GrupoCampaña;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles y Permisos (Spatie)
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Usuarios TI solicitados
        $users = [
            [
                'name' => 'Jorge Ramirez Penagos',
                'email' => 'jorgepenagos50@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'TI',
            ],
            [
                'name' => 'Mario Cesar Ramirez Moreno',
                'email' => 'mariocesar@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'Administrador',
            ],
            [
                'name' => 'Jorge Penagos (Admin)',
                'email' => 'jorgepenagos23@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'TI',
            ],
        ];

        foreach ($users as $userData) {
            $roleToAssign = $userData['role'];
            unset($userData['role']);
            $user = User::create($userData);
            $user->assignRole($roleToAssign);
        }

        // 3. Estructura mínima para que el importador de Excel funcione
        $d1 = Diplomado::create([
            'nombre' => 'NUEVO CODIGO',
            'costo_total' => 7000,
            'duracion_mes' => 6
        ]);

        $d2 = Diplomado::create([
            'nombre' => 'CUIDADOS DE ENFERMERIA',
            'costo_total' => 8000,
            'duracion_mes' => 8
        ]);

        CuentadeDeposito::create([
            'titular' => 'Administración CEDAC',
            'CLABE' => '000000000000000000',
            'banco' => 'BBVA',
            'numero_cuenta' => '00000000',
        ]);

        GrupoCampaña::create([
            'campaña' => 'Importación 2026',
            'grupo' => 'G1',
            'id_diplomado' => $d1->id,
        ]);

        $this->command->info('Database reset completed. 3 TI users created.');
    }
}
