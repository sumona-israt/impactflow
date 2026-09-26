<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('data_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->string('status');
            $table->json('errors')->nullable();
            $table->foreignUuid('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->timestamps();

            $table->index(['data_import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_rows');
    }
};
