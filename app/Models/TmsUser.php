<?php

namespace App\Models;

use Database\Factories\TmsUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsUser extends Model
{
    /** @use HasFactory<TmsUserFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'employee_code', 'phone', 'role', 'is_active', 'direct_manager_id', 'default_business_unit_id', 'timezone', 'avatar_color', 'password', 'deactivated_at'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
            'deactivated_at' => 'datetime',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'direct_manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'direct_manager_id');
    }

    public function defaultBusinessUnit(): BelongsTo
    {
        return $this->belongsTo(TmsBusinessUnit::class, 'default_business_unit_id');
    }

    public function businessUnits(): BelongsToMany
    {
        return $this->belongsToMany(TmsBusinessUnit::class, 'tms_business_unit_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(TmsTask::class, 'assignee_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(TmsTask::class, 'created_by_id');
    }

    public function isRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
