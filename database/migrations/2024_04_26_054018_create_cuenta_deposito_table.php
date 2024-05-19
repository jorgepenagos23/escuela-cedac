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
        Schema::create('cuenta_deposito', function (Blueprint $table) {
            $table->id();
            $table->string('CLABE')->nulleable();
            $table->string('numero_cuenta')->nullable();
            $table->string('titular')->default('CEDAC');
            $table->enum('banco',['BBVA','AZTECA','SANTANDER','SPIN',])->default('BBVA');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_deposito');
    }
};
