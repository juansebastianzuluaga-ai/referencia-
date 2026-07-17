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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Usuario que ejecuto la actividad');
            $table->string('event', 120)->comment('Nombre tecnico del evento registrado');
            $table->string('module', 120)->nullable()->comment('Modulo funcional asociado a la actividad');
            $table->text('description')->nullable()->comment('Descripcion legible de la actividad');
            $table->json('properties')->nullable()->comment('Datos adicionales de la actividad');
            $table->text('url')->nullable()->comment('URL desde donde se registro la actividad');
            $table->ipAddress('ip_address')->nullable()->comment('Direccion IP asociada a la actividad');
            $table->string('user_agent', 1023)->nullable()->comment('User agent asociado a la actividad');
            $table->string('tags')->nullable()->comment('Etiquetas separadas por coma para clasificar la actividad');
            $table->timestamps();

            $table->index(['event', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
