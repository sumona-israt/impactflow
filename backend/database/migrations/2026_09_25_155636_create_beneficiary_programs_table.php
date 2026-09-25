<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at');
            $table->string('status')->default('enrolled');
            $table->timestamps();

            $table->unique(['beneficiary_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_programs');
    }
};
