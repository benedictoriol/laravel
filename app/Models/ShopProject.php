<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopProject extends Model
{
    protected $fillable = [
        'shop_id','created_by','title','description','embroidery_size','canvas_used','category','base_price','min_order_qty','turnaround_days','is_customizable','is_active','preview_image_path','default_fulfillment_type','automation_profile_json','tags_json'
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'is_customizable' => 'boolean',
            'is_active' => 'boolean',
            'automation_profile_json' => 'array',
            'tags_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop() { return $this->belongsTo(Shop::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
