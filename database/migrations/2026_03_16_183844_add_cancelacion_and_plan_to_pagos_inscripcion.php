<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar motivo_cancelacion a pagos_colegiatura
        Schema::table('pagos_colegiatura', function (Blueprint $table) {
            $table->string('motivo_cancelacion')->nullable()->after('status');
        });

        // Agregar plan_pagos (JSON) a alumno_inscripcion
        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->json('plan_pagos')->nullable()->after('metodo_pago_inscripcion');
        });
    }

    public function down(): void
    {
        Schema::table('pagos_colegiatura', function (Blueprint $table) {
            $table->dropColumn('motivo_cancelacion');
        });

        Schema::table('alumno_inscripcion', function (Blueprint $table) {
            $table->dropColumn('plan_pagos');
        });
    }
};
