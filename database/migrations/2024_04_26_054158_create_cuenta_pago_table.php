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
        Schema::create('cuenta_pago', function (Blueprint $table) {
            $table->id();

            $table->decimal('total_colegiaturas');
            $table->string('Liquidacion');
           $table->unsignedBigInteger('idColegiatatura');
           $table->foreign('idColegiatatura')->references('id')->on('pagos_colegiatura')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_pago');
    }
};
