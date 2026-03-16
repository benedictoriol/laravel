<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignProof extends Model
{
    protected $fillable = [
        'design_customization_id',
        'proof_no',
        'generated_by',
        'preview_file_path',
        'annotated_notes',
        'pricing_snapshot_json',
        'status',
        'responded_by',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'pricing_snapshot_json' => 'array',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customization() { return $this->belongsTo(DesignCustomization::class, 'design_customization_id'); }
    public function generator() { return $this->belongsTo(User::class, 'generated_by'); }
    public function responder() { return $this->belongsTo(User::class, 'responded_by'); }
}
