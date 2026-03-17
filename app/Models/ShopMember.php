<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopMember extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'member_role',
        'position',
        'approval_status',
        'review_notes',
        'created_by_user_id',
        'reviewed_by_user_id',
        'reviewed_at',
        'employment_status',
        'joined_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
