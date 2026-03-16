<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BargainingOffer extends Model
{
    protected $fillable = [
        'design_post_id','job_post_application_id','parent_offer_id','offered_by_user_id','amount','estimated_days','message','status','responded_by','responded_at','expires_at','negotiation_round'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function designPost() { return $this->belongsTo(DesignPost::class); }
    public function application() { return $this->belongsTo(JobPostApplication::class, 'job_post_application_id'); }
    public function offeredBy() { return $this->belongsTo(User::class, 'offered_by_user_id'); }
    public function responder() { return $this->belongsTo(User::class, 'responded_by'); }
    public function parent() { return $this->belongsTo(BargainingOffer::class, 'parent_offer_id'); }
    public function children() { return $this->hasMany(BargainingOffer::class, 'parent_offer_id'); }
}
