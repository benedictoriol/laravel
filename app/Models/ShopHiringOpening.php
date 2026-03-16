<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopHiringOpening extends Model
{
    protected $fillable = [
        'shop_id',
        'title',
        'department',
        'employment_type',
        'description',
        'status',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
