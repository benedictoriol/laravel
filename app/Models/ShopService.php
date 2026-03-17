<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopService extends Model
{
    protected $fillable = [
        'shop_id',
        'service_name',
        'category',
        'description',
        'base_price',
        'unit_price',
        'stitch_range',
        'complexity_multiplier',
        'rush_fee_allowed',
        'rush_multiplier',
        'price_type',
        'min_order_qty',
        'turnaround_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'complexity_multiplier' => 'decimal:2',
            'rush_fee_allowed' => 'boolean',
            'rush_multiplier' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}