<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSuggestionRule extends Model
{
    protected $fillable = [
        'rule_code','rule_name','category','amount_type','amount_value','conditions_json','priority','is_active'
    ];

    protected function casts(): array
    {
        return [
            'amount_value' => 'decimal:2',
            'conditions_json' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
