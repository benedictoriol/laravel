<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignWorkflowEvent extends Model
{
    protected $fillable = [
        'design_customization_id',
        'actor_user_id',
        'event_type',
        'summary',
        'details',
        'event_meta_json',
    ];

    protected function casts(): array
    {
        return [
            'event_meta_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customization()
    {
        return $this->belongsTo(DesignCustomization::class, 'design_customization_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
