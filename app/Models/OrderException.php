<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderException extends Model
{
    protected $table = 'order_exceptions';

    protected $fillable = [
        'order_id',
        'exception_type',
        'severity',
        'status',
        'notes',
        'assigned_handler_id',
        'escalated_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedHandler()
    {
        return $this->belongsTo(User::class, 'assigned_handler_id');
    }
}
