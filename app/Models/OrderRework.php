<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderRework extends Model
{
    public const STATUS_OPEN = 'rework_open';
    public const STATUS_IN_PROGRESS = 'rework_in_progress';
    public const STATUS_DONE = 'rework_done';
    public const STATUS_RECHECK = 'rework_recheck';
    public const STATUS_CLOSED = 'rework_closed';

    protected $fillable = [
        'shop_id',
        'order_id',
        'quality_check_id',
        'design_customization_id',
        'production_package_id',
        'reason',
        'severity',
        'status',
        'internal_note',
        'progress_notes',
        'opened_by',
        'updated_by',
        'completed_at',
        'returned_to_qc_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'returned_to_qc_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop() { return $this->belongsTo(Shop::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function qualityCheck() { return $this->belongsTo(QualityCheck::class); }
    public function design() { return $this->belongsTo(DesignCustomization::class, 'design_customization_id'); }
    public function productionPackage() { return $this->belongsTo(DesignProductionPackage::class, 'production_package_id'); }
    public function opener() { return $this->belongsTo(User::class, 'opened_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
