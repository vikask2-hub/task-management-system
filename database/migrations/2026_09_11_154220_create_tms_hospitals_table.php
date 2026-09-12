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
        Schema::create('tms_hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('business_unit_id')->constrained('tms_business_units');
            $table->string('city')->index();
            $table->text('address')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('account_type')->nullable();
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_designation')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by_id')->constrained('tms_users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tms_hospitals');
    }
};
