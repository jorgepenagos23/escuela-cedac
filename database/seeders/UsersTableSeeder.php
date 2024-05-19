<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        foreach(range(1,10) as $index){
        \App\Models\User::create([
            'name' =>$faker->name,
            'email'=>$faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' =>Hash::make('password'),
            'role' =>$faker->randomElement(['admin','tutoria','admisiones','asesor']),
            'telefono' =>$faker->phoneNumber,
            'remember_token'=>Str::random(10),


        ]);

        }


    }

}
