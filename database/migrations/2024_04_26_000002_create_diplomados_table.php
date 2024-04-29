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
        Schema::create('diplomados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nulleable();
            $table->integer('duracion_mes')->default('7');
            $table->text('requisitos')->nulleable();
            $table->decimal('costo_total',10,2)->default(7000.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diplomados');
    }
};
