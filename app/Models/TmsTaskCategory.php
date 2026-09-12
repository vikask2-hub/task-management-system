<?php

namespace App\Models;

use Database\Factories\TmsTaskCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsTaskCategory extends Model
{
    /** @use HasFactory<TmsTaskCategoryFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'description', 'icon_key', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TmsTask::class, 'category_id');
    }
}
