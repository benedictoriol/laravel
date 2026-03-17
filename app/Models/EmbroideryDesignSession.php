<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbroideryDesignSession extends Model
{
    protected $table = 'embroidery_design_sessions';

    protected $fillable = [
        'user_id',
        'order_id',
        'shop_id',
        'name',
        'garment_type',
        'placement_area',
        'canvas_width',
        'canvas_height',
        'thread_palette_json',
        'design_json',
        'preview_svg',
        'estimated_stitches',
        'thread_color_count',
        'suggested_price',
        'pricing_confidence',
        'status',
        'version_no',
        'approved_version_no',
        'last_priced_at',
        'approved_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'thread_palette_json' => 'array',
            'design_json' => 'array',
            'suggested_price' => 'decimal:2',
            'pricing_confidence' => 'decimal:2',
            'last_priced_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function versions()
    {
        return $this->hasMany(EmbroideryDesignVersion::class, 'session_id');
    }
}
