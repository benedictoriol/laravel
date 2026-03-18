<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialConsumption extends Model
{
    protected $fillable = [
        'shop_id','order_id','design_customization_id','production_package_id','raw_material_id',
        'material_name_snapshot','material_category','material_code_snapshot','usage_type','unit',
        'estimated_quantity','reserved_quantity','consumed_quantity','remaining_available_stock','status','meta_json','created_by'
    ];

    protected function casts(): array
    {
        return [
            'estimated_quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'consumed_quantity' => 'decimal:4',
            'remaining_available_stock' => 'decimal:4',
            'meta_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order() { return $this->belongsTo(Order::class); }
    public function rawMaterial() { return $this->belongsTo(RawMaterial::class, 'raw_material_id'); }
    public function customization() { return $this->belongsTo(DesignCustomization::class, 'design_customization_id'); }
    public function productionPackage() { return $this->belongsTo(DesignProductionPackage::class, 'production_package_id'); }
}
