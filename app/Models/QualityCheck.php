<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QualityCheck extends Model
{
    protected $fillable = ['shop_id','order_id','checked_by','result','issue_notes','attachments_json','rework_required','action_taken','checked_at'];
    protected function casts(): array
    {
        return ['attachments_json'=>'array','rework_required'=>'boolean','checked_at'=>'datetime'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function order(){ return $this->belongsTo(Order::class); }
    public function checker(){ return $this->belongsTo(User::class,'checked_by'); }
}
