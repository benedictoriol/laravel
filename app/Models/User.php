<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'shop_id',
        'phone',
        'profile_photo',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function clientProfile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function ownedShops()
    {
        return $this->hasMany(Shop::class, 'owner_user_id');
    }

    public function shopMemberships()
    {
        return $this->hasMany(ShopMember::class);
    }

    public function clientOrders()
    {
        return $this->hasMany(Order::class, 'client_user_id');
    }

    public function notifications()
    {
        return $this->hasMany(PlatformNotification::class, 'user_id');
    }


    public function requestedRevisions()
    {
        return $this->hasMany(OrderRevision::class, 'requested_by');
    }

    public function handledRevisions()
    {
        return $this->hasMany(OrderRevision::class, 'handled_by');
    }

    public function assignedOrderAssignments()
    {
        return $this->hasMany(OrderAssignment::class, 'assigned_to');
    }

    public function createdOrderAssignments()
    {
        return $this->hasMany(OrderAssignment::class, 'assigned_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function recommendations()
    {
        return $this->hasMany(DssRecommendation::class, 'client_user_id');
    }

    public function operationalAlerts()
    {
        return $this->hasMany(OperationalAlert::class, 'user_id');
    }
}

