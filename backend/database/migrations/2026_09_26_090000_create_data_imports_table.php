<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');
            $table->string('file_path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('uploaded');
            $table->json('detected_headers');
            $table->json('column_mapping')->nullable();
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('valid_rows')->nullable();
            $table->unsignedInteger('duplicate_rows')->nullable();
            $table->unsignedInteger('invalid_rows')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
