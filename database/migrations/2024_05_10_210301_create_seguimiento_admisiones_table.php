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
        Schema::create('seguimiento_admisiones', function (Blueprint $table) {
            $table->id();


            $table->unsignedBigInteger('admisiones')->default(1)->nullable();


            $table->unsignedBigInteger('id_Alumno')->default(1)->nullable();
            $table->foreign('id_Alumno')->references('id')->on('alumno_inscripcion')->onDelete('cascade');
            $table->foreign('admisiones')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimiento_admisiones');
    }
};
