<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'model_type',
        'model_id',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('model_type', get_class($model))
                    ->where('model_id', $model->getKey());
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function getDescriptionAttribute()
    {
        $modelClass = class_basename($this->model_type);
        $event = match($this->event) {
            'created' => 'membuat',
            'updated' => 'memperbarui',
            'deleted' => 'menghapus',
            'restored' => 'memulihkan',
            default => $this->event
        };

        return "{$event} {$modelClass} #{$this->model_id}";
    }

    public function getChangesAttribute()
    {
        if ($this->old_values && $this->new_values) {
            return array_diff_assoc($this->new_values, $this->old_values);
        }
        
        return $this->new_values ?? [];
    }
}