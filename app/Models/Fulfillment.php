<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fulfillment extends Model
{
    protected $fillable = [
        'order_id',
        'fulfillment_type',
        'receiver_name',
        'receiver_contact',
        'cavite_location_id',
        'delivery_address',
        'courier_name',
        'tracking_number',
        'shipping_fee',
        'pickup_schedule_at',
        'shipped_at',
        'delivered_at',
        'received_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'shipping_fee' => 'decimal:2',
            'pickup_schedule_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
