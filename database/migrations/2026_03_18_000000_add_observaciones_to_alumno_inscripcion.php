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
            $table->text('observacion_tutorias')->nullable();
            $table->text('observacion_admisiones')->nullable();
            $table->string('estatus_excel')->nullable(); // Para guardar el estatus que viene del excel si no coincide con los internos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->dropColumn(['observacion_tutorias', 'observacion_admisiones', 'estatus_excel']);
        });
    }
};
