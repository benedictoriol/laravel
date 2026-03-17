<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = [
        'owner_user_id',
        'cavite_location_id',
        'shop_name',
        'slug',
        'description',
        'logo_path',
        'banner_path',
        'email',
        'phone',
        'address_line',
        'postal_code',
        'service_radius_km',
        'verification_status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_radius_km' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function members()
    {
        return $this->hasMany(ShopMember::class);
    }

    public function services()
    {
        return $this->hasMany(ShopService::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function designPosts()
    {
        return $this->hasMany(DesignPost::class, 'selected_shop_id');
    }

    public function metrics()
    {
        return $this->hasMany(DssShopMetric::class);
    }

    public function recommendations()
    {
        return $this->hasMany(DssRecommendation::class);
    }

    public function projects()
    {
        return $this->hasMany(ShopProject::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getNameAttribute(): string
    {
        return (string) $this->shop_name;
    }
}
