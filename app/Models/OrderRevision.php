<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRevision extends Model
{
    protected $table = 'order_revisions';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'revision_no',
        'requested_by',
        'handled_by',
        'revision_type',
        'request_notes',
        'response_notes',
        'preview_file_path',
        'status',
        'approved_at',
        'rejected_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
