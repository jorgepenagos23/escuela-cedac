<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();

            // Tipo: porcentaje | monto_fijo
            $table->enum('tipo', ['porcentaje', 'monto_fijo'])->default('porcentaje');
            $table->decimal('valor', 10, 2);          // ej: 15 (%) o 500 (MXN)

            // A quién aplica
            $table->enum('aplica_a', ['diplomado', 'tutor', 'general'])->default('general');
            $table->unsignedBigInteger('diplomado_id')->nullable();   // FK diplomados
            $table->unsignedBigInteger('tutor_id')->nullable();       // FK users (tutor)

            // Temporalidad
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // Estado del descuento
            $table->enum('estado', ['activo', 'suspendido', 'cancelado'])->default('activo');

            // Quién lo creó
            $table->unsignedBigInteger('creado_por')->nullable();

            $table->timestamps();

            $table->foreign('diplomado_id')->references('id')->on('diplomados')->nullOnDelete();
            $table->foreign('tutor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
