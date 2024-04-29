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
        Schema::create('pago_abono', function (Blueprint $table) {
            $table->id();
            $table->text('descripcion')->nullable();
            $table->date('fecha_abono');
            $table->decimal('monto_abono', 10, 2)->default(1600.00);
            $table->unsignedTinyInteger('porcentaje_aplicado')->nullable();

            $table->boolean('con_descuento')->default(false)->nullable();
            $table->boolean('aprobado_descuento')->default(false);

            $table->timestamps();

            $table->unsignedBigInteger('cuentadeposito');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('diplomado_id');

            $table->foreign('cuentadeposito')->references('id')->on('cuenta_deposito')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumnos')->onDelete('cascade');
            $table->foreign('diplomado_id')->references('id')->on('diplomados')->onDelete('cascade');
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
