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
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->string('correo')->nullable();
            $table->string('curp')->nullable();
            $table->string('nombre_emergencia')->nullable();
            $table->string('parentesco_emergencia')->nullable();
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->text('direccion_completa')->nullable();
            $table->string('metodo_pago_inscripcion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->dropColumn([
                'correo', 
                'curp', 
                'nombre_emergencia', 
                'parentesco_emergencia', 
                'estado', 
                'municipio', 
                'direccion_completa', 
                'metodo_pago_inscripcion'
            ]);
        });
    }
};
