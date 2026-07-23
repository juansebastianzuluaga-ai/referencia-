<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinica_auth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->enum('tipo', ['magic_link', 'otp']);
            $table->string('token')->nullable();       // SHA-256 del magic link
            $table->string('codigo_otp', 6)->nullable(); // Código SMS
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['clinica_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinica_auth_tokens');
    }
};
