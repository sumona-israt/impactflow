<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('local_id');
            $table->unsignedInteger('odoo_id')->nullable();
            $table->string('operation');
            $table->string('status');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('request_time')->nullable();
            $table->timestamp('response_time')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'local_id']);
            $table->index('status');
            $table->unique(['entity_type', 'local_id', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_sync_logs');
    }
};
