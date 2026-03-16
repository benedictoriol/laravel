<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DssShopMetric extends Model
{
    protected $table = 'dss_shop_metrics';

    protected $fillable = [
        'shop_id',
        'metric_date',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'avg_rating',
        'review_count',
        'completion_rate',
        'avg_turnaround_days',
        'active_staff_count',
        'open_job_posts_taken',
        'revenue_total',
        'price_competitiveness_score',
        'recommendation_score',
        'delay_risk_score',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'avg_rating' => 'decimal:2',
            'completion_rate' => 'decimal:4',
            'avg_turnaround_days' => 'decimal:2',
            'revenue_total' => 'decimal:2',
            'price_competitiveness_score' => 'decimal:2',
            'recommendation_score' => 'decimal:2',
            'delay_risk_score' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
