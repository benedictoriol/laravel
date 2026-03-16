<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_name',
        'garment_type',
        'size_label',
        'fabric_type',
        'placement_area',
        'placement_notes',
        'embroidery_type',
        'backing_type',
        'width_mm',
        'height_mm',
        'stitch_count',
        'thread_colors',
        'color_notes',
        'quantity',
        'unit_price',
        'line_total',
        'customization_notes',
        'mockup_approved',
        'mockup_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'mockup_approved' => 'boolean',
            'mockup_approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}