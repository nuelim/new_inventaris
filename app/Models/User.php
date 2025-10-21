<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function createdAssets()
    {
        return $this->hasMany(Asset::class, 'created_by');
    }

    public function assignedMaintenances()
    {
        return $this->hasMany(MaintenanceSchedule::class, 'assigned_to');
    }

    public function completedMaintenances()
    {
        return $this->hasMany(MaintenanceSchedule::class, 'completed_by');
    }

    public function assignedCalibrations()
    {
        return $this->hasMany(CalibrationSchedule::class, 'assigned_to');
    }

    public function completedCalibrations()
    {
        return $this->hasMany(CalibrationSchedule::class, 'completed_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}