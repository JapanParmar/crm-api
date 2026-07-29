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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('residential'); // residential, commercial, mixed_use, industrial, plot
            $table->string('status')->default('active'); // planning, active, under_construction, completed, on_hold
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('developer')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('total_units')->default(0);
            $table->integer('available_units')->default(0);
            $table->integer('sold_units')->default(0);
            $table->decimal('price_min', 15, 2)->nullable();
            $table->decimal('price_max', 15, 2)->nullable();
            $table->date('launch_date')->nullable();
            $table->date('possession_date')->nullable();
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
