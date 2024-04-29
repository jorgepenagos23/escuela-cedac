<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class AlumnoDiplomadoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $faker = Faker::create();
        $alumnoID=1;
        foreach(range(1,100) as $index){
            \App\Models\AlumnoDiplomado::create([
                'alumno_id' => $alumnoID++,
                'diplomado_id' => $faker->numberBetween(1,10),
            ]);
        }
    }
}
