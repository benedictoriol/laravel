<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DisputeCase extends Model
{
    protected $fillable = ['shop_id','order_id','complainant_user_id','assigned_handler_user_id','dispute_type','issue_summary','attachments_json','status','resolution','resolved_at'];
    protected function casts(): array
    {
        return ['attachments_json'=>'array','resolved_at'=>'datetime'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function order(){ return $this->belongsTo(Order::class); }
    public function complainant(){ return $this->belongsTo(User::class,'complainant_user_id'); }
    public function handler(){ return $this->belongsTo(User::class,'assigned_handler_user_id'); }
}
