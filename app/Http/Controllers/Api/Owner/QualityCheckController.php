<?php
namespace App\Http\Controllers\Api\Owner;
use App\Http\Controllers\Controller; use App\Models\QualityCheck; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class QualityCheckController extends Controller {
  public function index(Request $r): JsonResponse { return response()->json(QualityCheck::with(['order:id,order_number','checker:id,name'])->where('shop_id',$r->user()->shop_id)->latest('id')->get()); }
  public function store(Request $r): JsonResponse { $v=$r->validate(['order_id'=>'required|integer|exists:orders,id','result'=>'required|string|max:50','issue_notes'=>'nullable|string','attachments_json'=>'nullable|array','rework_required'=>'nullable|boolean','action_taken'=>'nullable|string','checked_at'=>'nullable|date']); $m=QualityCheck::create(array_merge($v,['shop_id'=>$r->user()->shop_id,'checked_by'=>$r->user()->id])); return response()->json($m->load(['order:id,order_number','checker:id,name']),201);} 
  public function update(Request $r,QualityCheck $qualityCheck): JsonResponse { abort_unless($qualityCheck->shop_id===$r->user()->shop_id,403); $v=$r->validate(['result'=>'sometimes|required|string|max:50','issue_notes'=>'nullable|string','attachments_json'=>'nullable|array','rework_required'=>'nullable|boolean','action_taken'=>'nullable|string','checked_at'=>'nullable|date']); $qualityCheck->update($v); return response()->json($qualityCheck->fresh()->load(['order:id,order_number','checker:id,name'])); }
}
