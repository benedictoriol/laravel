<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStageHistory extends Model
{
    protected $table = 'order_stage_history';

    protected $fillable = [
        'order_id',
        'stage_code',
        'stage_status',
        'started_at',
        'ended_at',
        'actor_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}