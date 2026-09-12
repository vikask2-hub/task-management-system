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
        Schema::create('tms_recurring_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('tms_task_categories');
            $table->string('priority', 12)->default('MEDIUM');
            $table->foreignId('business_unit_id')->constrained('tms_business_units');
            $table->foreignId('created_by_id')->constrained('tms_users');
            $table->foreignId('assignee_id')->constrained('tms_users');
            $table->boolean('verification_required')->default(true);
            $table->boolean('attachment_required')->default(false);
            $table->boolean('geo_requested')->default(false);
            $table->string('schedule_type', 30);
            $table->json('schedule_config')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('preferred_due_time')->default('17:00:00');
            $table->boolean('is_active')->default(true)->index();
            $table->date('last_generated_for_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tms_recurring_templates');
    }
};
