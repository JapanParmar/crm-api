<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('rera_number', 100)->nullable()->after('code');
            $table->string('state', 100)->nullable()->after('city');
            $table->text('google_map_url')->nullable()->after('location');
            $table->string('landmark', 255)->nullable()->after('location');
            $table->string('pincode', 10)->nullable()->after('state');
            $table->string('construction_stage')->nullable()->after('possession_date'); // planning/foundation/structure/finishing/completed
            $table->tinyInteger('construction_pct')->default(0)->after('construction_stage');

            // Excel fields
            $table->string('sr_no')->nullable()->after('id');
            $table->string('project_type')->nullable()->after('type');
            $table->string('project_status')->nullable()->after('status');
            $table->string('passession')->nullable()->after('possession_date');
            $table->string('price')->nullable()->after('budget');
            $table->string('size_sqft')->nullable()->after('price');
            $table->string('contact_person')->nullable()->after('developer');
            $table->string('contact_number')->nullable()->after('contact_person');
            $table->string('brochure_link')->nullable()->after('google_map_url');
            $table->text('remarks')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'rera_number', 'state', 'google_map_url', 'landmark',
                'pincode', 'construction_stage', 'construction_pct',
                'sr_no', 'project_type', 'project_status', 'passession',
                'price', 'size_sqft', 'contact_person', 'contact_number',
                'brochure_link', 'remarks',
            ]);
        });
    }
};
