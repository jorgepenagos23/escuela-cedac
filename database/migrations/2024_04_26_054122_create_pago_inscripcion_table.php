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
        Schema::create('pago_inscripcion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inscripcion');
            $table->text('descripcion')->nulleable();
            $table->decimal('monto_total',10,2)->default(7000.00); 
            $table->decimal('monto_inscripcion',10,2)->default(600.00)->nulleable();

            $table->unsignedBigInteger('cuentadeposito');
            $table->unsignedBigInteger('diplomado_id');
            $table->unsignedBigInteger('alumno_id');


            $table->foreign('cuentadeposito')->references('id')->on('cuenta_deposito')->onDelete('cascade');
            $table->foreign('diplomado_id')->references('id')->on('diplomados')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id')->on('alumnos')->onDelete('cascade');


            $table->timestamps('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pago_inscripcion');
    }
};
