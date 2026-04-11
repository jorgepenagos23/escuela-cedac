<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_campañas', function (Blueprint $table) {
            $table->unsignedBigInteger('tutor_id')->nullable()->after('id_diplomado');
            $table->foreign('tutor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('grupo_campañas', function (Blueprint $table) {
            $table->dropForeign(['tutor_id']);
            $table->dropColumn('tutor_id');
        });
    }
};
