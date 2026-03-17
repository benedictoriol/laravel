<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignMachineFile extends Model
{
    protected $fillable = [
        'design_digitizing_job_id',
        'design_customization_id',
        'design_version_no',
        'file_version',
        'file_type',
        'file_name',
        'file_path',
        'uploaded_by',
        'approval_state',
        'file_meta_json',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'design_version_no' => 'integer',
            'file_version' => 'integer',
            'file_meta_json' => 'array',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function digitizingJob()
    {
        return $this->belongsTo(DesignDigitizingJob::class, 'design_digitizing_job_id');
    }

    public function customization()
    {
        return $this->belongsTo(DesignCustomization::class, 'design_customization_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
