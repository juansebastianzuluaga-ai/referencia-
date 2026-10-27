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
        Schema::create('solicitud_evoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_referencia_id')->constrained('solicitudes_referencia')->cascadeOnDelete();
            $table->text('nota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_evoluciones');
    }
};
