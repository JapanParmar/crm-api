<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users');

            $table->string('type', 20); // call, whatsapp, email, meeting, site_visit
            $table->string('status', 20)->default('scheduled'); // scheduled, completed, missed, cancelled

            $table->timestamp('scheduled_at');
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
