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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Usuario destinatario');
            $table->string('type')->default('info')->comment('Tipo: info, success, warning, error');
            $table->string('title')->comment('Titulo de la notificacion');
            $table->text('message')->comment('Mensaje de la notificacion');
            $table->string('link')->nullable()->comment('Link opcional al hacer click');
            $table->timestamp('read_at')->nullable()->comment('Fecha de lectura');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'read_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
