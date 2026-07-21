<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_clinic_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('external_clinic_id')
                ->constrained('external_clinics')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('ID de la clínica externa');

            $table->enum('document_type', ['rut', 'chamber_of_commerce', 'legal_rep_id', 'other'])
                ->comment('Tipo de documento');

            $table->string('file_name', 255)->comment('Nombre original del archivo');
            $table->string('file_path', 500)->comment('Ruta en storage');
            $table->integer('file_size')->unsigned()->comment('Tamaño en bytes');
            $table->string('mime_type', 100)->comment('Tipo MIME');

            $table->timestamp('uploaded_at')->useCurrent();

            $table->index('external_clinic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_clinic_documents');
    }
};
