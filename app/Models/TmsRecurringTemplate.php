<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsRecurringTemplate extends Model
{
    protected $fillable = ['name', 'title', 'description', 'category_id', 'priority', 'business_unit_id', 'created_by_id', 'assignee_id', 'verification_required', 'attachment_required', 'geo_requested', 'schedule_type', 'schedule_config', 'start_date', 'end_date', 'preferred_due_time', 'is_active', 'last_generated_for_date'];

    protected function casts(): array
    {
        return [
            'schedule_config' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'last_generated_for_date' => 'date',
            'verification_required' => 'boolean',
            'attachment_required' => 'boolean',
            'geo_requested' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TmsTaskCategory::class, 'category_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(TmsBusinessUnit::class, 'business_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'created_by_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TmsTask::class, 'recurring_template_id');
    }
}
