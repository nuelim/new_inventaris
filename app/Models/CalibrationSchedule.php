<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CalibrationSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'frequency',
        'custom_frequency_days',
        'scheduled_date',
        'due_date',
        'completed_date',
        'status',
        'assigned_to',
        'completed_by',
        'calibration_vendor',
        'certificate_number',
        'certificate_expiry',
        'cost',
        'notes',
        'completion_notes',
        'is_recurring',
        'next_calibration_date',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'certificate_expiry' => 'date',
        'next_calibration_date' => 'date',
        'cost' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    protected static function booted()
    {
        static::updating(function ($schedule) {
            if ($schedule->isDirty('status') && $schedule->status === 'completed' && $schedule->is_recurring) {
                $schedule->calculateNextCalibration();
            }
        });
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeScheduled(Builder $query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress(Builder $query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query)
    {
        return $query->where('status', '!=', 'completed')
                    ->where('due_date', '<', now());
    }

    public function scopeDueSoon(Builder $query, $days = 7)
    {
        return $query->where('status', 'scheduled')
                    ->where('due_date', '<=', now()->addDays($days))
                    ->where('due_date', '>=', now());
    }

    public function scopeByFrequency(Builder $query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeByVendor(Builder $query, $vendor)
    {
        return $query->where('calibration_vendor', 'like', "%{$vendor}%");
    }

    public function scopeAssignedTo(Builder $query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    public function scopeCertificateExpiringSoon(Builder $query, $days = 30)
    {
        return $query->where('certificate_expiry', '<=', now()->addDays($days))
                    ->where('certificate_expiry', '>=', now());
    }

    public function isOverdue()
    {
        return $this->status !== 'completed' && $this->due_date < now();
    }

    public function isDueSoon($days = 7)
    {
        return $this->status === 'scheduled' &&
               $this->due_date <= now()->addDays($days) &&
               $this->due_date >= now();
    }

    public function getDaysUntilDueAttribute()
    {
        if ($this->status === 'completed') return null;
        return now()->diffInDays($this->due_date, false);
    }

    public function getPriorityAttribute()
    {
        if ($this->isOverdue()) return 'high';
        if ($this->isDueSoon(3)) return 'high';
        if ($this->isDueSoon(7)) return 'medium';
        return 'low';
    }

    public function isCertificateValid()
    {
        return $this->certificate_expiry && $this->certificate_expiry >= now();
    }

    public function getCertificateRemainingDaysAttribute()
    {
        if (!$this->certificate_expiry) return null;
        return now()->diffInDays($this->certificate_expiry, false);
    }

    public function calculateNextCalibration()
    {
        if (!$this->is_recurring) return;

        $frequencyDays = match($this->frequency) {
            'monthly' => 30,
            'quarterly' => 90,
            'semi_annual' => 180,
            'annual' => 365,
            'custom' => $this->custom_frequency_days,
            default => 365
        };

        $this->next_calibration_date = $this->completed_date->addDays($frequencyDays);
    }

    public function markAsCompleted($completedBy, $notes = null, $certificateNumber = null, $certificateExpiry = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completed_by' => $completedBy,
            'completion_notes' => $notes,
            'certificate_number' => $certificateNumber,
            'certificate_expiry' => $certificateExpiry,
        ]);

        if ($this->is_recurring) {
            $this->createNextCalibration();
        }
    }

    public function createNextCalibration()
    {
        if (!$this->is_recurring) return;

        $frequencyDays = match($this->frequency) {
            'monthly' => 30,
            'quarterly' => 90,
            'semi_annual' => 180,
            'annual' => 365,
            'custom' => $this->custom_frequency_days,
            default => 365
        };

        CalibrationSchedule::create([
            'asset_id' => $this->asset_id,
            'title' => $this->title,
            'description' => $this->description,
            'frequency' => $this->frequency,
            'custom_frequency_days' => $this->custom_frequency_days,
            'scheduled_date' => $this->completed_date->addDays($frequencyDays),
            'due_date' => $this->completed_date->addDays($frequencyDays),
            'assigned_to' => $this->assigned_to,
            'calibration_vendor' => $this->calibration_vendor,
            'is_recurring' => true,
            'status' => 'scheduled',
        ]);
    }
}