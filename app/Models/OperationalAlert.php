<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalAlert extends Model
{
    protected $fillable = [
        'shop_id',
        'order_id',
        'user_id',
        'category',
        'severity',
        'title',
        'message',
        'reference_type',
        'reference_id',
        'status',
        'resolved_at',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'meta_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop() { return $this->belongsTo(Shop::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
}
