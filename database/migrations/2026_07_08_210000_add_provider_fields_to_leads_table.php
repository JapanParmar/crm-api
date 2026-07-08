<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Original enquiry date from lead provider portals
            $table->date('lead_date')->nullable()->after('email');

            // Service type categorization (new_project, resale, rental)
            $table->string('service_type', 30)->nullable()->after('source');

            // Location details from provider sheets
            $table->string('city', 100)->nullable()->after('preferred_location');
            $table->string('locality', 255)->nullable()->after('city');

            // External portal reference IDs
            $table->string('listing_id', 100)->nullable()->after('notes');
            $table->string('lead_provider_ref', 100)->nullable()->after('listing_id');

            // Indexes for new filter/chart fields
            $table->index('service_type');
            $table->index('city');
            $table->index('lead_date');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['service_type']);
            $table->dropIndex(['city']);
            $table->dropIndex(['lead_date']);

            $table->dropColumn([
                'lead_date',
                'service_type',
                'city',
                'locality',
                'listing_id',
                'lead_provider_ref',
            ]);
        });
    }
};
