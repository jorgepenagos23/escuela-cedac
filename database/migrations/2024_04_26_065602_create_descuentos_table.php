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
        Schema::create('descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pago_abono_id')->unique()->nullable();
            $table->unsignedTinyInteger('porcentaje')->nullable();
            $table->boolean('aprobado')->default(false);
            $table->timestamps();

            $table->foreign('pago_abono_id')->references('id')->on('pago_abono')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descuentos');
    }
};
