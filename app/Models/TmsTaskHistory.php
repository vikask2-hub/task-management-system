<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsTaskHistory extends Model
{
    protected $fillable = ['task_id', 'from_status', 'to_status', 'changed_by_id', 'reason'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TmsTask::class, 'task_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'changed_by_id');
    }
}
