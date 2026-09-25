<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2 only had `users`/`roles` to reference (bigint ids). Phase 3
     * introduces UUID-keyed entities (programs, beneficiaries, ...), so
     * `entity_id` needs to hold either. Postgres needs an explicit USING
     * cast for a bigint->varchar column change that Schema::table()->change()
     * can't express without doctrine/dbal; SQLite (used by the test suite)
     * has no such restriction, so it takes the portable path.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE audit_logs ALTER COLUMN entity_id TYPE VARCHAR(36) USING entity_id::VARCHAR');

            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('entity_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE audit_logs ALTER COLUMN entity_id TYPE BIGINT USING NULLIF(entity_id, \'\')::BIGINT');

            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id')->nullable()->change();
        });
    }
};
