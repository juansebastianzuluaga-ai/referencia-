<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->string('cedula_representante', 20)->nullable()->after('representante_legal');
            $table->text('observaciones')->nullable()->after('cedula_representante');
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropColumn(['cedula_representante', 'observaciones']);
        });
    }
};
