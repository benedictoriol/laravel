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
        'workflow_status',
        'current_version_no',
        'approved_version_no',
        'submitted_at',
        'last_revision_requested_at',
        'locked_at',
        'production_status',
        'production_ready_at',
        'latest_production_package_id',
        'color_mapping_json',
        'risk_flags_json',
        'suggested_quote_basis_json',
        'production_meta_json',
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
            'current_version_no' => 'integer',
            'approved_version_no' => 'integer',
            'last_priced_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_revision_requested_at' => 'datetime',
            'locked_at' => 'datetime',
            'production_ready_at' => 'datetime',
            'color_mapping_json' => 'array',
            'risk_flags_json' => 'array',
            'suggested_quote_basis_json' => 'array',
            'production_meta_json' => 'array',
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
    public function workflowEvents() { return $this->hasMany(DesignWorkflowEvent::class); }
    public function productionPackages() { return $this->hasMany(DesignProductionPackage::class); }
    public function latestProductionPackage() { return $this->belongsTo(DesignProductionPackage::class, 'latest_production_package_id'); }
}


