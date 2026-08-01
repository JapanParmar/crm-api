<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('hra', 12, 2)->nullable()->after('salary');
            $table->decimal('allowances', 12, 2)->nullable()->after('hra');
            $table->decimal('deductions', 12, 2)->nullable()->after('allowances');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['hra', 'allowances', 'deductions']);
        });
    }
};
