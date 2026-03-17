<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaviteLocation extends Model
{
    protected $fillable = [
        'location_type',
        'name',
        'province_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
