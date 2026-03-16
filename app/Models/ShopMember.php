<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopMember extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'member_role',
        'employment_status',
        'joined_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}