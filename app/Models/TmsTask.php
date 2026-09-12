<?php

namespace App\Models;

use Database\Factories\TmsTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsTask extends Model
{
    /** @use HasFactory<TmsTaskFactory> */
    use HasFactory;

    protected $fillable = ['task_number', 'title', 'description', 'category_id', 'priority', 'status', 'business_unit_id', 'hospital_id', 'hospital_name_snapshot', 'address_snapshot', 'assignee_id', 'assignee_role_snapshot', 'created_by_id', 'planned_at', 'due_at', 'started_at', 'submitted_at', 'completed_at', 'cancelled_at', 'verification_required', 'attachment_required', 'geo_requested', 'expected_outcome', 'outcome', 'completion_notes', 'person_met', 'person_designation', 'next_action', 'follow_up_at', 'blocker_reason', 'parent_task_id', 'recurring_template_id', 'occurrence_key', 'source', 'reopened_count', 'archived_at'];

    protected function casts(): array
    {
        return [
            'planned_at' => 'datetime',
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'archived_at' => 'datetime',
            'verification_required' => 'boolean',
            'attachment_required' => 'boolean',
            'geo_requested' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TmsTaskCategory::class, 'category_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(TmsBusinessUnit::class, 'business_unit_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(TmsHospital::class, 'hospital_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'created_by_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TmsTaskComment::class, 'task_id')->whereNull('deleted_at')->oldest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TmsTaskHistory::class, 'task_id')->latest();
    }

    public function isOverdue(): bool
    {
        return $this->due_at->isPast() && ! in_array($this->status, ['COMPLETED', 'CANCELLED'], true);
    }
}
