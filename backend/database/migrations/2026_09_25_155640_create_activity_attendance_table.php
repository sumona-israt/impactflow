<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->boolean('attended')->default(true);
            $table->timestamps();

            $table->unique(['activity_id', 'beneficiary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_attendance');
    }
};
