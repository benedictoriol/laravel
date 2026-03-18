<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialMovement extends Model
{
    protected $fillable = [
        'shop_id','order_id','raw_material_id','material_consumption_id','source','destination','quantity','movement_date','responsible_user_id','notes'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'movement_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function order() { return $this->belongsTo(Order::class); }
    public function rawMaterial() { return $this->belongsTo(RawMaterial::class, 'raw_material_id'); }
    public function consumption() { return $this->belongsTo(MaterialConsumption::class, 'material_consumption_id'); }
    public function responsiblePerson() { return $this->belongsTo(User::class, 'responsible_user_id'); }
}
