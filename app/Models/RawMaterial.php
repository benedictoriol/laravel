<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RawMaterial extends Model
{
    protected $fillable = ['shop_id','supplier_id','material_name','category','color','unit','stock_quantity','reorder_level','cost_per_unit','last_restocked_at','status','notes'];
    protected function casts(): array
    {
        return ['stock_quantity' => 'decimal:2','reorder_level'=>'decimal:2','cost_per_unit'=>'decimal:2','last_restocked_at'=>'datetime'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
}
