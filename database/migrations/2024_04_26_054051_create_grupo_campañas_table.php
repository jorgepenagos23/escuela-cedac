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
        Schema::create('grupo_campañas', function (Blueprint $table) {
            $table->id();
            $table->string('campaña')->nulleable();
            $table->string('grupo')->nulleable();
            $table->unsignedBigInteger('id_diplomado')->nullable();
            $table->foreign('id_diplomado')->references('id')->on('diplomados')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_campañas');
    }
};
