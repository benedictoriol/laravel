<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageThreadParticipant extends Model
{
    protected $fillable = [
        'thread_id',
        'user_id',
        'joined_at',
        'last_read_at',
        'is_muted',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_read_at' => 'datetime',
            'is_muted' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function thread()
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
