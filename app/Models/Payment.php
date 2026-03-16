<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'client_user_id',
        'shop_id',
        'payment_method_id',
        'payment_type',
        'amount',
        'proof_file_path',
        'transaction_reference',
        'payer_name',
        'payment_status',
        'paid_at',
        'confirmed_at',
        'confirmed_by',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}