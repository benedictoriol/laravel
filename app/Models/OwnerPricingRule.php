<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerPricingRule extends Model
{
    protected $fillable = ['shop_id', 'rule_type', 'rule_key', 'label', 'config_json', 'is_active'];

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
