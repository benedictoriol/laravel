<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignCustomizationSnapshot extends Model
{
    protected $fillable = [
        'design_customization_id',
        'version_no',
        'captured_by',
        'change_summary',
        'snapshot_json',
        'pricing_snapshot_json',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'pricing_snapshot_json' => 'array',
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
        return $this->belongsTo(User::class, 'captured_by');
    }
}
