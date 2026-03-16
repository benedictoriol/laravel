<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerSetting extends Model
{
    protected $fillable = [
        'shop_id','shop_name','address','contact_number','contact_email','operating_hours',
        'default_labor_rate','rush_fee_percent','default_profit_margin','minimum_order_quantity',
        'max_rush_orders_per_day','cancellation_rules','notification_settings_json',
        'delivery_defaults_json','ui_preferences_json','security_settings_json',
        'workflow_automation_settings_json','document_settings_json','approval_settings_json',
    ];

    protected function casts(): array
    {
        return [
            'default_labor_rate' => 'decimal:2',
            'rush_fee_percent' => 'decimal:2',
            'default_profit_margin' => 'decimal:2',
            'notification_settings_json' => 'array',
            'delivery_defaults_json' => 'array',
            'ui_preferences_json' => 'array',
            'security_settings_json' => 'array',
            'workflow_automation_settings_json' => 'array',
            'document_settings_json' => 'array',
            'approval_settings_json' => 'array',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
