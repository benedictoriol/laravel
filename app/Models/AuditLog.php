<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_user_id',
        'shop_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values_json',
        'new_values_json',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values_json' => 'array',
            'new_values_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
