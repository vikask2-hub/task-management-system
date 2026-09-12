<?php

namespace App\Models;

use Database\Factories\TmsHospitalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TmsHospital extends Model
{
    /** @use HasFactory<TmsHospitalFactory> */
    use HasFactory;

    protected $fillable = ['name', 'business_unit_id', 'city', 'address', 'pincode', 'account_type', 'primary_contact_name', 'primary_contact_designation', 'phone', 'email', 'is_active', 'created_by_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
        return $this->hasMany(TmsTask::class, 'hospital_id');
    }
}
