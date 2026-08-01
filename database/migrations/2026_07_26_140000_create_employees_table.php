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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('department')->default('Sales'); // Sales, Marketing, HR, IT, Finance, Operations, Construction
            $table->string('designation')->default('Sales Executive');
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract, intern, probation
            $table->string('status')->default('active'); // active, on_leave, suspended, terminated
            $table->date('joining_date');
            $table->decimal('salary', 12, 2)->nullable();
            
            // New requested fields from Excel
            $table->string('sr_no')->nullable();
            $table->string('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('office_email')->nullable();
            $table->string('manager')->nullable();
            $table->string('device_assigned')->nullable();
            $table->string('laptop_model')->nullable();
            $table->string('laptop_serial_number')->nullable();
            $table->string('mobile_model')->nullable();
            $table->string('mobile_serial_number')->nullable();
            $table->string('location')->nullable();

            $table->string('pan_number')->nullable();
            $table->string('aadhar_number')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
