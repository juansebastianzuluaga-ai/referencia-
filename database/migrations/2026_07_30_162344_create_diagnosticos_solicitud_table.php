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
        Schema::create('diagnosticos_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_referencia_id')->constrained('solicitudes_referencia')->cascadeOnDelete();
            $table->string('codigo_cie10', 20);
            $table->string('descripcion', 400);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnosticos_solicitud');
    }
};
