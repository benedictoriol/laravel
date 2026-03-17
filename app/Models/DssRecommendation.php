<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DssRecommendation extends Model
{
    protected $table = 'dss_recommendations';

    protected $fillable = [
        'client_user_id',
        'shop_id',
        'generated_for_type',
        'basis',
        'score',
        'rank_position',
        'context_json',
        'generated_at',
    ];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'context_json' => 'array',
            'generated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }
}
