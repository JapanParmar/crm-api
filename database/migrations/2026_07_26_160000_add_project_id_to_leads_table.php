<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leads', 'project_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('priority')->constrained('projects')->onDelete('set null');
            });
        }

        if (!Schema::hasColumn('site_visits', 'project_id')) {
            Schema::table('site_visits', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('lead_id')->constrained('projects')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_visits', 'project_id')) {
            Schema::table('site_visits', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }

        if (Schema::hasColumn('leads', 'project_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }
    }
};
