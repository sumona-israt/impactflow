<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->string('local_id');
            $table->string('odoo_model');
            $table->unsignedInteger('odoo_id');
            $table->timestamps();

            $table->unique(['entity_type', 'local_id', 'odoo_model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_mappings');
    }
};
