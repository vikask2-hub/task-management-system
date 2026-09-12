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
        Schema::create('tms_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tms_tasks')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('tms_users');
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tms_task_comments');
    }
};
