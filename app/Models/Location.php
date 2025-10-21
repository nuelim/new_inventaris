<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'building',
        'floor',
        'room',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'manager_name',
        'manager_email',
        'is_active',
    ];

    protected $casts = [
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

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByProvince($query, $province)
    {
        return $query->where('province', $province);
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address;
        if ($this->city) $address .= ', ' . $this->city;
        if ($this->province) $address .= ', ' . $this->province;
        if ($this->postal_code) $address .= ' ' . $this->postal_code;
        return $address;
    }

    public function getBuildingInfoAttribute()
    {
        $info = $this->building;
        if ($this->floor) $info .= ' Lantai ' . $this->floor;
        if ($this->room) $info .= ' Ruang ' . $this->room;
        return $info;
    }

    public function getAssetCountAttribute()
    {
        return $this->assets()->count();
    }

    public function getActiveAssetCountAttribute()
    {
        return $this->assets()->where('status', 'active')->count();
    }

    public function getTotalValueAttribute()
    {
        return $this->assets()->sum('current_value');
    }

    public function getMaintenanceRequiredCountAttribute()
    {
        return $this->assets()
            ->whereHas('maintenanceSchedules', function ($query) {
                $query->where('status', '!=', 'completed')
                      ->where('due_date', '<', now()->addDays(30));
            })
            ->count();
    }

    public function getCalibrationRequiredCountAttribute()
    {
        return $this->assets()
            ->whereHas('calibrationSchedules', function ($query) {
                $query->where('status', '!=', 'completed')
                      ->where('due_date', '<', now()->addDays(30));
            })
            ->count();
    }
}