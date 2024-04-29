<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CuentaDepositoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
      $faker = Faker::create();
      $bancos=['BBVA','AZTECA','OXXO','SANTANDER'];

      foreach(range(1,4) as $index){
        $bancosEntrada = $bancos[array_rand($bancos)];

        \App\Models\CuentadeDeposito::create([

          
        'CLABE' =>$faker->randomNumber(5),
        'numero_cuenta'=>$faker->randomNumber(5),
        'titular' =>$faker->name,
        'banco'=>$bancosEntrada,

        ]);

      }


    }
}
