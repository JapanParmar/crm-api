<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('attended_by')->constrained('users');

            $table->string('project_name');
            $table->string('location')->nullable();

            $table->string('status', 20)->default('scheduled'); // scheduled, completed, cancelled, no_show

            $table->timestamp('scheduled_at');
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('interested')->nullable(); // null = pending, true = interested, false = not interested

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_id');
            $table->index('attended_by');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
