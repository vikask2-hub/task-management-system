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
        Schema::create('tms_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('tms_task_categories');
            $table->string('priority', 12)->default('MEDIUM')->index();
            $table->string('status', 20)->default('TODO');
            $table->foreignId('business_unit_id')->constrained('tms_business_units');
            $table->foreignId('hospital_id')->nullable()->constrained('tms_hospitals')->nullOnDelete();
            $table->string('hospital_name_snapshot')->nullable();
            $table->text('address_snapshot')->nullable();
            $table->foreignId('assignee_id')->constrained('tms_users');
            $table->string('assignee_role_snapshot', 10);
            $table->foreignId('created_by_id')->constrained('tms_users');
            $table->dateTime('planned_at');
            $table->dateTime('due_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->boolean('verification_required')->default(true);
            $table->boolean('attachment_required')->default(false);
            $table->boolean('geo_requested')->default(false);
            $table->text('expected_outcome')->nullable();
            $table->text('outcome')->nullable();
            $table->text('completion_notes')->nullable();
            $table->string('person_met')->nullable();
            $table->string('person_designation')->nullable();
            $table->text('next_action')->nullable();
            $table->dateTime('follow_up_at')->nullable();
            $table->text('blocker_reason')->nullable();
            $table->unsignedBigInteger('parent_task_id')->nullable()->index();
            $table->unsignedBigInteger('recurring_template_id')->nullable()->index();
            $table->string('occurrence_key')->nullable()->unique();
            $table->string('source', 20)->default('MANUAL');
            $table->unsignedSmallInteger('reopened_count')->default(0);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();

            $table->index(['assignee_id', 'planned_at']);
            $table->index(['business_unit_id', 'planned_at']);
            $table->index(['status', 'due_at']);
            $table->index(['category_id', 'completed_at']);
            $table->index(['created_by_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tms_tasks');
    }
};
