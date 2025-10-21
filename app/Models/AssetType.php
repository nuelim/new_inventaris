<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'depreciation_years',
        'requires_calibration',
        'requires_maintenance',
        'is_active',
    ];

    protected $casts = [
        'requires_calibration' => 'boolean',
        'requires_maintenance' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRequiresCalibration($query)
    {
        return $query->where('requires_calibration', true);
    }

    public function scopeRequiresMaintenance($query)
    {
        return $query->where('requires_maintenance', true);
    }

    public function getAssetCountAttribute()
    {
        return $this->assets()->count();
    }

    public function getActiveAssetCountAttribute()
    {
        return $this->assets()->where('status', 'active')->count();
    }

    public function getMaintenanceRequiredAssetCountAttribute()
    {
        return $this->assets()
            ->whereHas('maintenanceSchedules', function ($query) {
                $query->where('status', '!=', 'completed');
            })
            ->count();
    }

    public function getCalibrationRequiredAssetCountAttribute()
    {
        return $this->assets()
            ->whereHas('calibrationSchedules', function ($query) {
                $query->where('status', '!=', 'completed');
            })
            ->count();
    }
}