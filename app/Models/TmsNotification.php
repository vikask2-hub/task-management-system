<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'entity_type', 'entity_id', 'is_read', 'read_at'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'user_id');
    }
}
