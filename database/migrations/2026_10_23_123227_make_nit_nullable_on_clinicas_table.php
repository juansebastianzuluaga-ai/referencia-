<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las clínicas que se registran solas desde el formulario público de
     * remisión no tienen NIT todavía — el personal lo completa después en
     * el módulo de clínicas al aprobarlas.
     */
    public function up(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('nit', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table): void {
            $table->string('nit', 20)->nullable(false)->change();
        });
    }
};
