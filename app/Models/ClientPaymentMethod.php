<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'method_type',
        'account_name',
        'account_number',
        'provider',
        'instructions',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
