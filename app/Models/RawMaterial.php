<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    protected $fillable = [
        'shop_id',
        'supplier_id',
        'material_name',
        'material_code',
        'sku',
        'category',
        'description',
        'color',
        'unit',
        'stock_quantity',
        'reserved_quantity',
        'minimum_stock_level',
        'reorder_level',
        'reorder_threshold',
        'maximum_stock_capacity',
        'cost_per_unit',
        'unit_purchase_cost',
        'latest_cost',
        'average_cost',
        'selling_cost_contribution',
        'estimated_usage_per_order_unit',
        'usage_measurement',
        'supplier_name',
        'supplier_code',
        'preferred_supplier',
        'thread_color',
        'thread_type',
        'brand',
        'thickness',
        'fabric_type',
        'fabric_color',
        'texture',
        'backing_type',
        'weight',
        'last_restocked_at',
        'status',
        'stock_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
            'minimum_stock_level' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'reorder_threshold' => 'decimal:2',
            'maximum_stock_capacity' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'unit_purchase_cost' => 'decimal:2',
            'latest_cost' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'selling_cost_contribution' => 'decimal:2',
            'estimated_usage_per_order_unit' => 'decimal:4',
            'last_restocked_at' => 'datetime',
        ];
    }

    public function shop() { return $this->belongsTo(Shop::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function consumptions() { return $this->hasMany(MaterialConsumption::class); }
    public function movements() { return $this->hasMany(MaterialMovement::class); }

    public function getAvailableStockAttribute(): float
    {
        return max(0, (float) $this->stock_quantity - (float) ($this->reserved_quantity ?? 0));
    }

    public function refreshStockStatus(): string
    {
        $available = $this->available_stock;
        $min = (float) ($this->minimum_stock_level ?? 0);
        $reorder = (float) ($this->reorder_threshold ?? $this->reorder_level ?? 0);

        return match (true) {
            $available <= 0 => 'out_of_stock',
            $available <= max($min, 0.0001) => 'critical',
            $available <= max($reorder, 0.0001) => 'low_stock',
            default => 'in_stock',
        };
    }
}
