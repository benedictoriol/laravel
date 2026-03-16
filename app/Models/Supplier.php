<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $fillable = ['shop_id','name','contact_person','phone','email','address','materials_supplied','lead_time_days','notes','status'];
    public function shop(){ return $this->belongsTo(Shop::class); }
}
