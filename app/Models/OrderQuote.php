<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderQuote extends Model
{
    protected $fillable = [
        'order_id',
        'shop_id',
        'quoted_by',
        'quote_number',
        'version_no',
        'status',
        'valid_until',
        'subtotal',
        'digitizing_fee',
        'material_fee',
        'labor_fee',
        'rush_fee',
        'shipping_fee',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'terms_and_notes',
        'client_response_notes',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'digitizing_fee' => 'decimal:2',
            'material_fee' => 'decimal:2',
            'labor_fee' => 'decimal:2',
            'rush_fee' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'responded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(OrderQuoteItem::class, 'order_quote_id');
    }

    public function quotedBy()
    {
        return $this->belongsTo(User::class, 'quoted_by');
    }
}