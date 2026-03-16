<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'client_user_id',
        'shop_id',
        'source_design_post_id',
        'service_id',
        'latest_quote_id',
        'approved_quote_id',
        'order_type',
        'status',
        'current_stage',
        'payment_status',
        'fulfillment_type',
        'subtotal',
        'customization_fee',
        'rush_fee',
        'discount_amount',
        'total_amount',
        'quoted_at',
        'approved_at',
        'payment_due_date',
        'due_date',
        'completed_at',
        'cancelled_at',
        'cancelled_reason',
        'delivery_address',
        'customer_notes',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'customization_fee' => 'decimal:2',
            'rush_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'quoted_at' => 'datetime',
            'approved_at' => 'datetime',
            'payment_due_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function service()
    {
        return $this->belongsTo(ShopService::class, 'service_id');
    }

    public function designPost()
    {
        return $this->belongsTo(DesignPost::class, 'source_design_post_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function revisions()
    {
        return $this->hasMany(OrderRevision::class);
    }

    public function stageHistory()
    {
        return $this->hasMany(OrderStageHistory::class);
    }

    public function progressLogs()
    {
        return $this->hasMany(OrderProgressLog::class);
    }

    public function quotes()
    {
        return $this->hasMany(OrderQuote::class);
    }

    public function assignments()
    {
        return $this->hasMany(OrderAssignment::class);
    }

    public function fulfillment()
    {
        return $this->hasOne(Fulfillment::class);
    }


    public function exceptions()
    {
        return $this->hasMany(OrderException::class);
    }

    public function customizations()
    {
        return $this->hasMany(DesignCustomization::class);
    }

    public function approvedQuote()
    {
        return $this->belongsTo(OrderQuote::class, 'approved_quote_id');
    }

    public function latestQuote()
    {
        return $this->belongsTo(OrderQuote::class, 'latest_quote_id');
    }
}