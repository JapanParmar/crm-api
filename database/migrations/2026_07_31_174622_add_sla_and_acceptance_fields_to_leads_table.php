<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('assignment_status', 30)->nullable()->after('assigned_at'); // pending, accepted, rejected, expired
            $table->dateTime('accepted_at')->nullable()->after('assignment_status');
            $table->dateTime('sla_expires_at')->nullable()->after('accepted_at');
            $table->foreignId('reassigned_from')->nullable()->after('sla_expires_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['reassigned_from']);
            $table->dropColumn(['assignment_status', 'accepted_at', 'sla_expires_at', 'reassigned_from']);
        });
    }
};
