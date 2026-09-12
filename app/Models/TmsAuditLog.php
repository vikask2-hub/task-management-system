<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmsAuditLog extends Model
{
    protected $fillable = ['actor_user_id', 'action', 'entity_type', 'entity_id', 'business_unit_id', 'metadata', 'ip_hash', 'user_agent'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(TmsUser::class, 'actor_user_id');
    }
}
