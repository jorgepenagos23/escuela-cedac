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
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->unsignedBigInteger('descuento_id')->nullable()->after('diplomado_id');
            $table->decimal('monto_descuento', 10, 2)->default(0)->after('descuento_id');
            
            $table->foreign('descuento_id')->references('id')->on('descuentos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->dropForeign(['descuento_id']);
            $table->dropColumn(['descuento_id', 'monto_descuento']);
        });
    }
};
