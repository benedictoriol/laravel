<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderQuoteItem extends Model
{
    protected $fillable = [
        'order_quote_id',
        'order_item_id',
        'line_label',
        'line_type',
        'quantity',
        'unit',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function quote()
    {
        return $this->belongsTo(OrderQuote::class, 'order_quote_id');
    }
}