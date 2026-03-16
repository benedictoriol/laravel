<?php
namespace App\Http\Controllers\Api\Owner;
use App\Http\Controllers\Controller; use App\Models\Message; use App\Models\MessageThread; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class MessageThreadController extends Controller {
  public function index(Request $r): JsonResponse { return response()->json(MessageThread::with(['messages.sender:id,name'])->where('shop_id',$r->user()->shop_id)->latest('last_message_at')->get()); }
  public function store(Request $r): JsonResponse { $v=$r->validate(['order_id'=>'nullable|integer|exists:orders,id','type'=>'nullable|string|max:50','title'=>'required|string|max:150','participant_user_ids_json'=>'nullable|array']); $m=MessageThread::create(array_merge($v,['shop_id'=>$r->user()->shop_id,'type'=>$v['type']??'group','last_message_at'=>now()])); return response()->json($m->load(['messages.sender:id,name']),201);} 
  public function postMessage(Request $r, MessageThread $thread): JsonResponse { abort_unless($thread->shop_id===$r->user()->shop_id,403); $v=$r->validate(['message'=>'required|string','attachments_json'=>'nullable|array']); $msg=Message::create(['thread_id'=>$thread->id,'sender_user_id'=>$r->user()->id,'message'=>$v['message'],'attachments_json'=>$v['attachments_json']??null]); $thread->forceFill(['last_message_at'=>now()])->save(); return response()->json($msg->load('sender:id,name'),201);} 
}
