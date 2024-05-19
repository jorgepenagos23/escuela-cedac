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
        Schema::create('alumno_inscripcion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inscripcion');
            $table->decimal('saldo',10,2)->default(7000.00);
            $table->decimal('monto_inscripcion',10,2)->default(600.00)->nulleable();
            $table->string('nombre_alumno')->nullable();
            $table->string('celular')->nullable();
            $table->string('adicional')->nullable();

            $table->unsignedBigInteger('asesor')->default(1)->nullable();
            $table->unsignedBigInteger('tutor')->default(2)->nullable();
            $table->unsignedBigInteger('grupo_campa');
            $table->date('fecha_primer_pago_colegiatura')->nullable();


            $table->unsignedBigInteger('cuentadeposito');
            $table->unsignedBigInteger('diplomado_id');


            $table->foreign('cuentadeposito')->references('id')->on('cuenta_deposito')->onDelete('cascade');
            $table->foreign('diplomado_id')->references('id')->on('diplomados')->onDelete('cascade');
            $table->foreign('asesor')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tutor')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('grupo_campa')->references('id')->on('grupo_campañas')->onDelete('cascade');


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
