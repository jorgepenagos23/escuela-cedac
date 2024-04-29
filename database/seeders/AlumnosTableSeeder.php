<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use  Faker\Factory as Faker;

class AlumnosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $faker = Faker::create();

        
        $matriculas =['MA23','MA24'];
        
        foreach(range(1,100) as $index){

        $matriculaAleatoria = $matriculas[array_rand($matriculas)];

        $matriculaCompleta = $matriculaAleatoria . $faker->numberBetween(100,999);
        \App\Models\Alumno::create([
            'nombre_completo' => $faker->name,
            'matricula' => $matriculaCompleta,
            'fecha_nacimiento'=>$faker->date,
            'correo' =>$faker->email,
            'telefono'=>$faker->phoneNumber,
            'direccion'=>$faker->address,
            'id_diplomado' =>$faker->numberBetween(1,10),
        ]);

        }

        


    }
}
