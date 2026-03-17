<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignProductionPackage extends Model
{
    protected $fillable = [
        'design_customization_id',
        'created_by',
        'version_no',
        'package_no',
        'status',
        'preview_path',
        'proof_summary_json',
        'design_metadata_json',
        'quote_basis_json',
        'thread_mapping_json',
        'risk_flags_json',
        'production_summary_json',
        'internal_note',
        'qc_note',
        'handed_off_at',
    ];

    protected function casts(): array
    {
        return [
            'proof_summary_json' => 'array',
            'design_metadata_json' => 'array',
            'quote_basis_json' => 'array',
            'thread_mapping_json' => 'array',
            'risk_flags_json' => 'array',
            'production_summary_json' => 'array',
            'handed_off_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customization()
    {
        return $this->belongsTo(DesignCustomization::class, 'design_customization_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
