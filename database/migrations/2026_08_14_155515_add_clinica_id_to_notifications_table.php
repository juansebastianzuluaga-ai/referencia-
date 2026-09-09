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
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('clinica_id')->nullable()->after('user_id')->constrained('clinicas')->cascadeOnDelete()->comment('Clínica destinataria (alternativa a user_id)');
            $table->index(['clinica_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['clinica_id']);
            $table->dropIndex(['clinica_id', 'read_at']);
            $table->dropColumn('clinica_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
