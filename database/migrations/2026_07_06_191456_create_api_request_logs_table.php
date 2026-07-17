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
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_credential_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->integer('response_status')->nullable();
            $table->integer('response_time')->nullable()->comment('Milisegundos');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('api_credential_id');
            $table->index('response_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
