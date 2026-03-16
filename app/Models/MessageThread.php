<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageThread extends Model
{
    protected $fillable = ['shop_id','order_id','type','title','participant_user_ids_json','last_message_at'];
    protected function casts(): array
    {
        return ['participant_user_ids_json'=>'array','last_message_at'=>'datetime'];
    }
    public function shop(){ return $this->belongsTo(Shop::class); }
    public function order(){ return $this->belongsTo(Order::class); }
    public function messages(){ return $this->hasMany(Message::class,'thread_id'); }
}
