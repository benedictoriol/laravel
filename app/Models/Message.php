<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Message extends Model
{
    protected $fillable = ['thread_id','sender_user_id','message','attachments_json'];
    protected function casts(): array
    {
        return ['attachments_json'=>'array'];
    }
    public function thread(){ return $this->belongsTo(MessageThread::class,'thread_id'); }
    public function sender(){ return $this->belongsTo(User::class,'sender_user_id'); }
}
