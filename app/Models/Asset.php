<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'asset_type_id',
        'location_id',
        'supplier_id',
        'brand',
        'model',
        'serial_number',
        'part_number',
        'purchase_price',
        'purchase_date',
        'warranty_expiry',
        'condition',
        'status',
        'current_value',
        'qr_code',
        'barcode',
        'specifications',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'specifications' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($asset) {
            if (!$asset->qr_code) {
                $asset->qr_code = 'QR-' . uniqid();
            }
            if (!$asset->barcode) {
                $asset->barcode = 'BAR-' . uniqid();
            }
        });
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function calibrationSchedules()
    {
        return $this->hasMany(CalibrationSchedule::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType(Builder $query, $typeId)
    {
        return $query->where('asset_type_id', $typeId);
    }

    public function scopeByLocation(Builder $query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCondition(Builder $query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopeWarrantyExpiringSoon(Builder $query, $days = 30)
    {
        return $query->where('warranty_expiry', '<=', now()->addDays($days))
                    ->where('warranty_expiry', '>=', now());
    }

    public function scopeNeedsMaintenance(Builder $query)
    {
        return $query->whereHas('maintenanceSchedules', function ($q) {
            $q->where('status', '!=', 'completed')
              ->where('due_date', '<=', now());
        });
    }

    public function scopeNeedsCalibration(Builder $query)
    {
        return $query->whereHas('calibrationSchedules', function ($q) {
            $q->where('status', '!=', 'completed')
              ->where('due_date', '<=', now());
        });
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
    }

    public function isUnderWarranty()
    {
        return $this->warranty_expiry && $this->warranty_expiry >= now();
    }

    public function getWarrantyRemainingDaysAttribute()
    {
        if (!$this->warranty_expiry) return null;
        return now()->diffInDays($this->warranty_expiry, false);
    }

    public function getAgeInMonthsAttribute()
    {
        if (!$this->purchase_date) return null;
        return now()->diffInMonths($this->purchase_date);
    }

    public function getDepreciatedValueAttribute()
    {
        if (!$this->purchase_price || !$this->asset_type->depreciation_years) {
            return $this->purchase_price;
        }

        $annualDepreciation = $this->purchase_price / $this->asset_type->depreciation_years;
        $monthsPassed = $this->age_in_months;
        $monthlyDepreciation = $annualDepreciation / 12;
        $totalDepreciation = $monthlyDepreciation * $monthsPassed;

        return max(0, $this->purchase_price - $totalDepreciation);
    }

    public function getNextMaintenanceDateAttribute()
    {
        $nextMaintenance = $this->maintenanceSchedules()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>', now())
            ->orderBy('scheduled_date')
            ->first();

        return $nextMaintenance?->scheduled_date;
    }

    public function getNextCalibrationDateAttribute()
    {
        $nextCalibration = $this->calibrationSchedules()
            ->where('status', 'scheduled')
            ->where('scheduled_date', '>', now())
            ->orderBy('scheduled_date')
            ->first();

        return $nextCalibration?->scheduled_date;
    }

    public function getOverdueMaintenanceCountAttribute()
    {
        return $this->maintenanceSchedules()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();
    }

    public function getOverdueCalibrationCountAttribute()
    {
        return $this->calibrationSchedules()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();
    }
}