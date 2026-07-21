<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_clinics', function (Blueprint $table) {
            $table->id();

            $table->string('nit', 20)->unique()->comment('NIT de la clínica');
            $table->string('business_name', 255)->comment('Razón social');
            $table->string('trade_name', 255)->nullable()->comment('Nombre comercial');

            $table->string('email', 255)->unique()->comment('Correo electrónico principal');
            $table->string('phone', 20)->comment('Teléfono de contacto');
            $table->string('mobile', 20)->nullable()->comment('Celular de contacto');

            $table->text('address')->comment('Dirección completa');
            $table->string('city', 100)->comment('Ciudad');
            $table->string('department', 100)->comment('Departamento');

            $table->string('legal_rep_name', 255)->comment('Nombre del representante legal');
            $table->foreignId('legal_rep_id_type_id')
                ->constrained('identification_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('Tipo de identificación del representante legal');
            $table->string('legal_rep_id_number', 30)->comment('Número de identificación del representante legal');

            $table->string('password')->comment('Contraseña hasheada');

            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'inactive'])
                ->default('pending')
                ->comment('Estado de la solicitud/cuenta');
            $table->text('rejection_reason')->nullable()->comment('Motivo de rechazo');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que aprobó la solicitud');
            $table->timestamp('approved_at')->nullable()->comment('Fecha de aprobación');

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario que rechazó la solicitud');
            $table->timestamp('rejected_at')->nullable()->comment('Fecha de rechazo');

            $table->boolean('must_change_password')->default(false)->comment('Debe cambiar contraseña en el próximo login');
            $table->timestamp('email_verified_at')->nullable()->comment('Fecha de verificación de email');
            $table->timestamp('last_login_at')->nullable()->comment('Fecha del último login');
            $table->integer('failed_login_attempts')->default(0)->comment('Intentos fallidos de login');

            $table->timestamps();
            $table->softDeletes();

            $table->index('nit');
            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_clinics');
    }
};
