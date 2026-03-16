<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignCustomization extends Model
{
    protected $fillable = [
        'design_post_id',
        'order_id',
        'user_id',
        'name',
        'garment_type',
        'placement_area',
        'fabric_type',
        'width_mm',
        'height_mm',
        'color_count',
        'stitch_count_estimate',
        'complexity_level',
        'special_styles_json',
        'notes',
        'artwork_path',
        'preview_path',
        'status',
        'estimated_base_price',
        'estimated_total_price',
        'pricing_breakdown_json',
        'design_session_json',
        'preview_meta_json',
        'pricing_confidence_score',
        'pricing_strategy',
        'last_priced_at',
        'approved_proof_id',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'height_mm' => 'decimal:2',
            'estimated_base_price' => 'decimal:2',
            'estimated_total_price' => 'decimal:2',
            'special_styles_json' => 'array',
            'pricing_breakdown_json' => 'array',
            'design_session_json' => 'array',
            'preview_meta_json' => 'array',
            'pricing_confidence_score' => 'decimal:2',
            'last_priced_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function designPost() { return $this->belongsTo(DesignPost::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function proofs() { return $this->hasMany(DesignProof::class); }
    public function snapshots() { return $this->hasMany(DesignCustomizationSnapshot::class); }
    public function approvedProof() { return $this->belongsTo(DesignProof::class, 'approved_proof_id'); }
}

