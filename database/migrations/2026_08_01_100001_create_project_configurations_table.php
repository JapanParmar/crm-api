<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('bhk_type'); // 1BHK, 2BHK, 3BHK, 4BHK, Penthouse, Commercial, Plot, Villa
            $table->decimal('carpet_area_min', 10, 2)->nullable();
            $table->decimal('carpet_area_max', 10, 2)->nullable();
            $table->decimal('price_from', 15, 2)->nullable();
            $table->decimal('price_to', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_configurations');
    }
};
