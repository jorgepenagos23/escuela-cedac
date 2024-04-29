<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();

   

            $table->string('nombre_completo');
            $table->string('matricula' )->unique();
            $table->date('fecha_nacimiento');
            $table->string('correo');
            $table->string('telefono');
            $table->string('direccion')->nulleable();


            $table->unsignedBigInteger('id_diplomado');
            $table->foreign('id_diplomado')->references('id')->on('diplomados');



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
