<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupplyOrder extends Model
{
    protected $fillable = ['shop_id','supplier_id','po_number','materials_json','quantity_total','quantity_received','total_cost','ordered_at','expected_arrival_at','actual_arrival_at','received_at','status','delivery_status','notes','approved_by'];
    protected function casts(): array
    {
        return ['materials_json'=>'array','quantity_total'=>'decimal:2','quantity_received'=>'decimal:2','total_cost'=>'decimal:2','ordered_at'=>'date','expected_arrival_at'=>'date','actual_arrival_at'=>'date','received_at'=>'date'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function supplier(){ return $this->belongsTo(Supplier::class); }
    public function approver(){ return $this->belongsTo(User::class,'approved_by'); }
}
