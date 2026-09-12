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
        Schema::create('tms_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('employee_code')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('role', 10)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('direct_manager_id')->nullable()->constrained('tms_users')->nullOnDelete();
            $table->foreignId('default_business_unit_id')->nullable()->constrained('tms_business_units')->nullOnDelete();
            $table->string('timezone')->default('Asia/Kolkata');
            $table->string('avatar_color')->default('#2563eb');
            $table->string('password');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tms_business_unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tms_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tms_business_unit_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['tms_user_id', 'tms_business_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tms_business_unit_user');
        Schema::dropIfExists('tms_users');
    }
};
