<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;


class DiplomadoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $faker = Faker::create();

        foreach(range(1,10)as $index){
        \App\Models\Diplomado::create([
            'nombre' => $faker->sentence(2),
        ]);


        }


    }
}
