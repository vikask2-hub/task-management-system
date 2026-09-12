<?php

namespace App\Models;

use Database\Factories\TmsBusinessUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsBusinessUnit extends Model
{
    /** @use HasFactory<TmsBusinessUnitFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'city', 'state', 'region', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(TmsUser::class, 'tms_business_unit_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TmsTask::class, 'business_unit_id');
    }
}
