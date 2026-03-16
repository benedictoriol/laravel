<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProgressLog extends Model
{
    protected $table = 'order_progress_logs';

    protected $fillable = [
        'order_id',
        'status',
        'title',
        'description',
        'actor_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public const UPDATED_AT = null;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}