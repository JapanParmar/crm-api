<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number')->unique();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('email')->nullable();

            // Classification
            $table->string('source', 50); // magicbricks, 99acres, housing, meta_ads, etc.
            $table->string('status', 30)->default('new'); // new, contacted, qualified, site_visit, negotiation, closed_won, closed_lost, on_hold
            $table->string('priority', 20)->default('medium'); // low, medium, high, urgent

            // Property interest
            $table->string('property_type', 30)->nullable(); // apartment, villa, plot, commercial, etc.
            $table->unsignedBigInteger('budget_min')->nullable();
            $table->unsignedBigInteger('budget_max')->nullable();
            $table->string('preferred_location')->nullable();
            $table->string('project_interest')->nullable();
            $table->string('bhk_preference')->nullable();

            // Lead intelligence
            $table->unsignedTinyInteger('score')->default(0); // 0–100
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            // Contact tracking
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            // Counters (denormalized for performance)
            $table->unsignedSmallInteger('follow_up_count')->default(0);
            $table->unsignedSmallInteger('site_visit_count')->default(0);

            // Dedup
            $table->boolean('is_duplicate')->default(false);
            $table->foreignId('duplicate_of')->nullable()->constrained('leads')->nullOnDelete();

            // Source tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('status');
            $table->index('source');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
