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
        Schema::create('solicitud_referencia_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_referencia_id')
                ->constrained('solicitudes_referencia')
                ->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['solicitud_referencia_id', 'created_at'], 'sol_ref_eventos_solicitud_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_referencia_eventos');
    }
};
