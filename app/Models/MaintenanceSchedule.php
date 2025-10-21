<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'type',
        'frequency',
        'custom_frequency_days',
        'scheduled_date',
        'due_date',
        'completed_date',
        'status',
        'assigned_to',
        'completed_by',
        'cost',
        'notes',
        'completion_notes',
        'is_recurring',
        'next_maintenance_date',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'next_maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    protected static function booted()
    {
        static::updating(function ($schedule) {
            if ($schedule->isDirty('status') && $schedule->status === 'completed' && $schedule->is_recurring) {
                $schedule->calculateNextMaintenance();
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

    public function scopeByType(Builder $query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFrequency(Builder $query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeAssignedTo(Builder $query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
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
        if ($this->type === 'emergency') return 'high';
        if ($this->isOverdue()) return 'high';
        if ($this->isDueSoon(3)) return 'high';
        if ($this->isDueSoon(7)) return 'medium';
        return 'low';
    }

    public function calculateNextMaintenance()
    {
        if (!$this->is_recurring) return;

        $frequencyDays = match($this->frequency) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'semi_annual' => 180,
            'annual' => 365,
            'custom' => $this->custom_frequency_days,
            default => 30
        };

        $this->next_maintenance_date = $this->completed_date->addDays($frequencyDays);
    }

    public function markAsCompleted($completedBy, $notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'completed_by' => $completedBy,
            'completion_notes' => $notes,
        ]);

        if ($this->is_recurring) {
            $this->createNextMaintenance();
        }
    }

    public function createNextMaintenance()
    {
        if (!$this->is_recurring) return;

        $frequencyDays = match($this->frequency) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'semi_annual' => 180,
            'annual' => 365,
            'custom' => $this->custom_frequency_days,
            default => 30
        };

        MaintenanceSchedule::create([
            'asset_id' => $this->asset_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'frequency' => $this->frequency,
            'custom_frequency_days' => $this->custom_frequency_days,
            'scheduled_date' => $this->completed_date->addDays($frequencyDays),
            'due_date' => $this->completed_date->addDays($frequencyDays),
            'assigned_to' => $this->assigned_to,
            'is_recurring' => true,
            'status' => 'scheduled',
        ]);
    }
}