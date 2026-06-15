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
        Schema::table('diplomados', function (Blueprint $table) {
            $table->text('requisitos')->nullable()->after('costo_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diplomados', function (Blueprint $table) {
            $table->dropColumn('requisitos');
        });
    }
};
