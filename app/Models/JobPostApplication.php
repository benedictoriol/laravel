<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPostApplication extends Model
{
    protected $fillable = [
        'design_post_id',
        'shop_id',
        'owner_user_id',
        'proposed_price',
        'estimated_days',
        'available_start_date',
        'message',
        'sample_work_link',
        'attachment_path',
        'status',
        'applied_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_price' => 'decimal:2',
            'available_start_date' => 'date',
            'applied_at' => 'datetime',
            'responded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function designPost()
    {
        return $this->belongsTo(DesignPost::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function bargainingOffers()
    {
        return $this->hasMany(BargainingOffer::class, 'job_post_application_id');
    }
}
