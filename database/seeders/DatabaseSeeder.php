<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Diplomado;
use App\Models\GrupoCampaña;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory(0)->create();

        \App\Models\User::factory()->create([ //1
            'name' => 'Mario Cesar Ramirez Moreno ',
            'email' => 'mariocesar@gmail.com',
            'role' => 'admin',
        ]);

        \App\Models\User::factory()->create([ //2
            'name' => 'Jorge Ramirez Penagos',
            'email' => 'jorgepenagos50@gmail.com',
            'role' => 'admin',
        ]);


        //3
        \App\Models\User::factory()->create([
            'name' => 'Kelly ',
            'email' => 'kelly@gmail.com',
            'role' => 'tutoria',
        ]);

        //4
        \App\Models\User::factory()->create([
            'name' => 'Carlos G ',
            'email' => 'carlos@gmail.com',
            'role' => 'tutoria',
        ]);
        //5
        \App\Models\User::factory()->create([
            'name' => 'Veronica ',
            'email' => 'veronica@gmail.com',
            'role' => 'asesor',
        ]);
        //6
        \App\Models\User::factory()->create([
            'name' => 'Hannia',
            'email' => 'hannia@gmail.com',
            'role' => 'asesor',
        ]);
        //7
        \App\Models\User::factory()->create([
            'name' => 'Lili',
            'email' => 'lili@gmail.com',
            'role' => 'tutoria',
        ]);
        ///8
        \App\Models\User::factory()->create([
            'name' => 'Ivan',
            'email' => 'ivan@gmail.com',
            'role' => 'asesor',
        ]);

        //9
        \App\Models\User::factory()->create([
            'name' => 'Rubicel',
            'email' => 'rubicel@gmail.com',
            'role' => 'asesor',
        ]);

        //10
        \App\Models\User::factory()->create([
            'name' => 'Itzi',
            'email' => 'itzi@gmail.com',
            'role' => 'asesor',
        ]);





        \App\Models\Diplomado::create([

            'nombre' => 'NUEVO CODIGO',
        ]);

        \App\Models\Diplomado::create([

            'nombre' => 'CUIDADOS DE ENFERMERIA',
        ]);



        \App\Models\CuentadeDeposito::create([
            'titular' => 'MARIO CESAR',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '437',
        ]);
        \App\Models\CuentadeDeposito::create([
            'titular' => 'MARIO CESAR',
            'CLABE' => '4130',
            'banco' => 'BBVA',
            'numero_cuenta' => '',
        ]);
        \App\Models\CuentadeDeposito::create([
            'titular' => 'MARIO CESAR',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '413',
        ]);
        \App\Models\CuentadeDeposito::create([
            'titular' => 'MARIO CESAR',
            'CLABE' => '',
            'banco' => 'SPIN',
            'numero_cuenta' => '2119',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'MARIO CESAR',
            'CLABE' => '8409',
            'banco' => 'SPIN',
            'numero_cuenta' => '',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'JUAN CARLOS',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '1740',
        ]);


        \App\Models\CuentadeDeposito::create([
            'titular' => 'JUAN CARLOS',
            'CLABE' => '8305',
            'banco' => 'BBVA',
            'numero_cuenta' => '',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'JUAN CARLOS',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '6830',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'GEOVANY',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '7737',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'GEOVANY',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '7793',
        ]);
        \App\Models\CuentadeDeposito::create([
            'titular' => 'GEOVANY',
            'CLABE' => '7934',
            'banco' => 'BBVA',
            'numero_cuenta' => '',
        ]);


        \App\Models\CuentadeDeposito::create([
            'titular' => 'GILDA TINO',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '5109',
        ]);


        \App\Models\CuentadeDeposito::create([
            'titular' => 'GILDA TINO',
            'CLABE' => '1099',
            'banco' => 'BBVA',
            'numero_cuenta' => '',
        ]);

        \App\Models\CuentadeDeposito::create([
            'titular' => 'GILDA TINO',
            'CLABE' => '',
            'banco' => 'BBVA',
            'numero_cuenta' => '7523',
        ]);



        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'A',
            'id_diplomado' => 1,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'B',
            'id_diplomado' => 1,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'C',
            'id_diplomado' => 1,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'D',
            'id_diplomado' => 1,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'A',
            'id_diplomado' => 2,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'B',
            'id_diplomado' => 2,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'C',
            'id_diplomado' => 2,
        ]);
        \App\Models\GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'D',
            'id_diplomado' => 2,
        ]);


        \App\Models\Inscripcion::create([

            'fecha_inscripcion' => '2024-01-12',
            'fecha_primer_pago_colegiatura' => '2024-06-13',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'Alfonso Camas',
            'celular' => '9614592435',
            'adicional' => 'No tiene',
            'asesor' => 5,
            'tutor' => 3,
            'grupo_campa' => 1,
            'cuentadeposito' => 2,
            'diplomado_id' => 1,

        ]);
        //////////////////////////////
        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-06-13',
            'fecha_inscripcion' => now(),
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'ALBA CONCEPCIÓN LÓPEZ SÁNCHEZ ',
            'celular' => '9813755572',
            'adicional' => '9811818915',
            'asesor' => 6,
            'tutor' => 3,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);


        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'EDUARDO MENA VAZQUEZ',
            'celular' => '4491066149',
            'adicional' => '449 1067593',
            'asesor' => 6,
            'tutor' => 7,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);


        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'CHACO PEREZ',
            'celular' => '4491066149',
            'adicional' => '449 1067593',
            'asesor' => 6,
            'tutor' => 7,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);



        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'MARCO AURELIO HERNÁNDEZ PARADA',
            'celular' => '449 260 0447',
            'adicional' => '449 106 6149',
            'asesor' => 6,
            'tutor' => 7,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'BEATRIZ ANGELICA ROJAS TORRES',
            'celular' => '3316912613',
            'adicional' => '3321546925',
            'asesor' => 8,
            'tutor' => 7,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);


        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'LEONOR VERENICE FERNANDEZ CASILLAS',
            'celular' => '6644054759',
            'adicional' => '',
            'asesor' => 6,
            'tutor' => 7,
            'grupo_campa' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Inscripcion::create([
            'fecha_primer_pago_colegiatura' => '2024-02-15',
            'fecha_inscripcion' => '2024-01-24',
            'monto_inscripcion' => 600,
            'nombre_alumno' => 'MARCO URIEL HERNÁNDEZ ACOSTA',
            'celular' => '213',
            'adicional' => '',
            'asesor' => 6,
            'tutor' => 7,
            'grupo_campa' => 2,
            'cuentadeposito' => 2,
            'diplomado_id' => 2,

        ]);







        $diplomado = Diplomado::create([
            'nombre' => 'JUICIOS ORALES',
            'duracion_mes' => 7,

            'costo_total' => 7000.00,
        ]);

        // Crear GrupoCampaña asociado al Diplomado
        $grupoCampaña = GrupoCampaña::create([
            'campaña' => '2024',
            'grupo' => 'A',
            'id_diplomado' => $diplomado->id,
        ]);





        ///PAGOS

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 7,
            'alumno_id' => 8,
            'cuentadeposito' => 2,
            'diplomado_id' => 2,

        ]);
        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 7,
            'alumno_id' => 8,
            'cuentadeposito' => 2,
            'diplomado_id' => 2,

        ]);
        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 7,
            'alumno_id' => 8,
            'cuentadeposito' => 2,
            'diplomado_id' => 2,

        ]);
        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 7,
            'alumno_id' => 8,
            'cuentadeposito' => 2,
            'diplomado_id' => 2,

        ]);


        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 1,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);


        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 2,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 2,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 2,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600.00,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 2,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);

        \App\Models\Pagos::create([

            'Fecha_PrimerContacto' => now(),
            'pago_colegiatura' => 1600.00,
            'status' => 'Activo',
            'tutor' => 3,
            'alumno_id' => 3,
            'cuentadeposito' => 1,
            'diplomado_id' => 1,

        ]);






    }
}
