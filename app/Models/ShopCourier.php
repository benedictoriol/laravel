<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopCourier extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'contact_person',
        'contact_number',
        'service_type',
        'coverage_area',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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
