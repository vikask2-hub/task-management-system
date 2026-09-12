<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsTaskComment extends Model
{
    protected $fillable = ['task_id', 'author_id', 'body', 'edited_at', 'deleted_at'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TmsTask::class, 'task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'author_id');
    }
}
