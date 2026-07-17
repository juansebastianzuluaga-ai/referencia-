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
        Schema::create('identification_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Codigo corto del tipo de identificacion');
            $table->string('name', 120)->unique()->comment('Nombre del tipo de identificacion');
            $table->boolean('is_active')->default(true)->comment('Indica si el tipo de identificacion esta activo');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identification_type_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('identification_number', 30)->comment('Numero de identificacion del usuario');
            $table->string('user_name', 60)->unique()->comment('Nombre de usuario usado para iniciar sesion');
            $table->string('first_name', 80)->comment('Primer nombre del usuario');
            $table->string('middle_name', 80)->nullable()->comment('Segundo nombre del usuario');
            $table->string('last_name', 80)->comment('Primer apellido del usuario');
            $table->string('sur_name', 80)->nullable()->comment('Segundo apellido del usuario');
            $table->string('email')->unique()->comment('Correo electronico del usuario');
            $table->string('job_title', 250)->nullable()->comment('Cargo laboral del usuario');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true)->comment('Indica si el usuario puede acceder al sistema');
            $table->boolean('must_change_password')->default(true)->comment('Indica si el usuario debe cambiar su contrasena en el proximo inicio de sesion');
            $table->boolean('must_update_profile')->default(false)->comment('Indica si el usuario debe actualizar su perfil');
            $table->timestamp('last_login_at')->nullable()->comment('Fecha y hora del ultimo inicio de sesion exitoso');
            $table->tinyInteger('failed_login_attempts')->nullable()->default(0)->comment('cantidad de ingreso fallidos');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['identification_type_id', 'identification_number']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('identification_types');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
