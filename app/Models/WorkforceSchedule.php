<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkforceSchedule extends Model
{
    protected $fillable = ['shop_id','user_id','order_id','shift_date','shift_start','shift_end','deadline_at','assignment_notes','is_day_off','is_overtime'];
    protected function casts(): array
    {
        return ['shift_date'=>'date','deadline_at'=>'datetime','is_day_off'=>'boolean','is_overtime'=>'boolean'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function order(){ return $this->belongsTo(Order::class); }
}

