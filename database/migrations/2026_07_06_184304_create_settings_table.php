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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Clave unica de la configuracion');
            $table->text('value')->nullable()->comment('Valor de la configuracion');
            $table->string('group')->default('general')->comment('Grupo al que pertenece la configuracion');
            $table->string('type')->default('string')->comment('Tipo de dato: string, boolean, integer, json');
            $table->timestamps();
            $table->softDeletes();

            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
