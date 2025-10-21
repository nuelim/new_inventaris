<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'tax_id',
        'payment_terms',
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
        if ($this->country) $address .= ', ' . $this->country;
        return $address;
    }

    public function getAssetCountAttribute()
    {
        return $this->assets()->count();
    }

    public function getTotalAssetValueAttribute()
    {
        return $this->assets()->sum('purchase_price');
    }

    public function getRecentAssetCountAttribute()
    {
        return $this->assets()->where('purchase_date', '>=', now()->subMonths(12))->count();
    }
}