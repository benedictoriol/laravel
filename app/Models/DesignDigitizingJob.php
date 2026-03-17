<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignDigitizingJob extends Model
{
    protected $fillable = [
        'design_customization_id',
        'design_proof_id',
        'order_id',
        'assigned_digitizer_user_id',
        'status',
        'digitizing_notes',
        'machine_file_status',
        'revision_count',
        'approval_state',
        'result_meta_json',
        'submitted_for_review_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_count' => 'integer',
            'result_meta_json' => 'array',
            'submitted_for_review_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customization()
    {
        return $this->belongsTo(DesignCustomization::class, 'design_customization_id');
    }

    public function proof()
    {
        return $this->belongsTo(DesignProof::class, 'design_proof_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function digitizer()
    {
        return $this->belongsTo(User::class, 'assigned_digitizer_user_id');
    }

    public function machineFiles()
    {
        return $this->hasMany(DesignMachineFile::class, 'design_digitizing_job_id');
    }
}
