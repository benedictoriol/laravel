<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignPost extends Model
{
    protected $fillable = [
        'client_user_id',
        'selected_shop_id',
        'converted_order_id',
        'cavite_location_id',
        'title',
        'description',
        'design_type',
        'fabric_type',
        'garment_type',
        'quantity',
        'target_budget',
        'deadline_date',
        'visibility',
        'status',
        'reference_file_path',
        'notes',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_budget' => 'decimal:2',
            'deadline_date' => 'date',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function selectedShop()
    {
        return $this->belongsTo(Shop::class, 'selected_shop_id');
    }

    public function applications()
    {
        return $this->hasMany(JobPostApplication::class, 'design_post_id');
    }

    public function convertedOrder()
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function customizations()
    {
        return $this->hasMany(DesignCustomization::class);
    }

    public function bargainingOffers()
    {
        return $this->hasMany(BargainingOffer::class);
    }
}
