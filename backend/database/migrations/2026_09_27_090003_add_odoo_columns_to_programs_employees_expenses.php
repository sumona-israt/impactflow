<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedInteger('odoo_project_id')->nullable()->after('progress');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('odoo_employee_id')->nullable()->after('status');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('odoo_synced')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('odoo_project_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('odoo_employee_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('odoo_synced');
        });
    }
};
