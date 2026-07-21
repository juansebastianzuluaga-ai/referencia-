<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_clinic_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('external_clinic_id')
                ->constrained('external_clinics')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la clínica externa');

            $table->string('previous_status', 50)->nullable()->comment('Estado anterior');
            $table->string('new_status', 50)->comment('Nuevo estado');

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que realizó el cambio');

            $table->text('change_reason')->nullable()->comment('Motivo del cambio');

            $table->string('ip_address', 45)->nullable()->comment('IP desde donde se hizo el cambio');
            $table->text('user_agent')->nullable()->comment('User agent del navegador');

            $table->timestamp('created_at')->useCurrent();

            $table->index('external_clinic_id');
            $table->index('new_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_clinic_requests');
    }
};
