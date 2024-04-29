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
        Schema::create('amortizacion', function (Blueprint $table) {
            $table->id();
            $table->date('periodo');
            

            $table->unsignedBigInteger('cuentadeposito');
            $table->unsignedBigInteger('id_pago');
            $table->unsignedBigInteger('id_inscripcion');

            $table->foreign('cuentadeposito')->references('id')->on('cuenta_deposito')->onDelete('cascade');    
            $table->foreign('id_pago')->references('id')->on('pago_abono')->onDelete('cascade');    
            $table->foreign('id_inscripcion')->references('id')->on('pago_inscripcion')->onDelete('cascade');    

            $table->timestamps();




        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amortizacion');
    }
};
