<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tower_id')->constrained('towers')->onDelete('cascade');
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('unit_number');
            $table->integer('floor_number');
            $table->string('bhk_type'); // 1BHK, 2BHK, 3BHK, 4BHK, Penthouse, Commercial, Plot
            $table->decimal('carpet_area', 10, 2)->nullable();
            $table->decimal('built_up_area', 10, 2)->nullable();
            $table->decimal('super_built_up_area', 10, 2)->nullable();
            $table->string('facing')->nullable(); // North, South, East, West, NE, NW, SE, SW
            // Pricing
            $table->decimal('base_price', 15, 2)->nullable();
            $table->decimal('price_per_sqft', 10, 2)->nullable();
            $table->decimal('floor_rise_charges', 12, 2)->default(0);
            $table->decimal('plc_charges', 12, 2)->default(0);
            $table->decimal('parking_charges', 12, 2)->default(0);
            $table->decimal('club_house_charges', 12, 2)->default(0);
            $table->decimal('gst_amount', 12, 2)->default(0);
            $table->decimal('total_price', 15, 2)->nullable();
            // Status
            $table->string('status')->default('available'); // available, reserved, hold, booked, sold, cancelled, blocked
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
