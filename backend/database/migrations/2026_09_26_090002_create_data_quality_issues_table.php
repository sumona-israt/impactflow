<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_quality_issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type');

            // FQCN (Expense) or bigint keys — same convention as workflow_instances.entity_id.
            $table->string('entity_id');
            $table->string('issue_type');
            $table->string('severity');
            $table->text('description');
            $table->string('status')->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_quality_issues');
    }
};
