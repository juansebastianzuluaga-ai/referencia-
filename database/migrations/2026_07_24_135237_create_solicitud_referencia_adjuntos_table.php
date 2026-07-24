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
        Schema::create('solicitud_referencia_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_referencia_id')
                ->constrained('solicitudes_referencia')
                ->cascadeOnDelete();
            $table->string('nombre_original', 255);
            $table->string('ruta', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano');
            $table->timestamps();

            $table->index('solicitud_referencia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_referencia_adjuntos');
    }
};
