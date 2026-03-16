<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAssignment extends Model
{
    protected $fillable = [
        'order_id',
        'assigned_to',
        'assigned_by',
        'assignment_role',
        'assignment_type',
        'status',
        'assigned_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
