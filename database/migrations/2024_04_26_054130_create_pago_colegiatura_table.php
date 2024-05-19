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
        Schema::create('pagos_colegiatura', function (Blueprint $table) {
            $table->id();

            $table->date('Fecha_PrimerContacto');
            $table->decimal('pago_colegiatura', 10, 2)->default(1600.00);
            $table->string('status')->default('activo');
            $table->unsignedBigInteger('tutor')->default(3)->nullable();

            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('cuentadeposito');
            $table->unsignedBigInteger('diplomado_id');

            $table->foreign('cuentadeposito')->references('id')->on('cuenta_deposito')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumno_inscripcion')->onDelete('cascade');
            $table->foreign('diplomado_id')->references('id')->on('diplomados')->onDelete('cascade');
            $table->foreign('tutor')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_abono');
    }
};
