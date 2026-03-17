<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbroideryDesignVersion extends Model
{
    protected $table = 'embroidery_design_versions';

    protected $fillable = [
        'session_id',
        'created_by',
        'version_no',
        'design_json',
        'preview_svg',
        'estimated_stitches',
        'thread_color_count',
        'suggested_price',
        'pricing_confidence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'design_json' => 'array',
            'suggested_price' => 'decimal:2',
            'pricing_confidence' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function session()
    {
        return $this->belongsTo(EmbroideryDesignSession::class, 'session_id');
    }
}
