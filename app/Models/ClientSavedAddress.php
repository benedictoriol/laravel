<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSavedAddress extends Model
{
    protected $fillable = [
        'client_profile_id',
        'label',
        'recipient_name',
        'recipient_phone',
        'cavite_location_id',
        'address_line',
        'postal_code',
        'is_default',
        'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(ClientProfile::class, 'client_profile_id');
    }
}
