<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone_number',
        'registration_date',
        'billing_contact_name',
        'billing_phone',
        'billing_email',
        'default_payment_method',
        'cavite_location_id',
        'default_address',
        'postal_code',
        'organization_name',
        'preferred_contact_method',
        'preferred_fulfillment_type',
        'mobile_push_enabled',
        'saved_measurements_json',
        'default_garment_preferences_json',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'mobile_push_enabled' => 'boolean',
            'saved_measurements_json' => 'array',
            'default_garment_preferences_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function addresses() { return $this->hasMany(ClientSavedAddress::class); }
}
