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
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropForeign(['clinica_id']);
        });

        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->unsignedBigInteger('clinica_id')->nullable()->change();
        });

        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->foreign('clinica_id')->references('id')->on('clinicas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->dropForeign(['clinica_id']);
        });

        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->unsignedBigInteger('clinica_id')->nullable(false)->change();
        });

        Schema::table('solicitudes_referencia', function (Blueprint $table) {
            $table->foreign('clinica_id')->references('id')->on('clinicas')->cascadeOnDelete();
        });
    }
};
