<?php
namespace App\Http\Controllers\Api\Owner;
use App\Http\Controllers\Controller; use App\Models\Supplier; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class SupplierController extends Controller {
  public function index(Request $r): JsonResponse { return response()->json(Supplier::where('shop_id',$r->user()->shop_id)->latest('id')->get()); }
  public function store(Request $r): JsonResponse { $v=$r->validate(['name'=>'required|string|max:150','contact_person'=>'nullable|string|max:150','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150','address'=>'nullable|string','materials_supplied'=>'nullable|string','lead_time_days'=>'nullable|integer|min:0','notes'=>'nullable|string','status'=>'nullable|string|max:50']); $m=Supplier::create(array_merge($v,['shop_id'=>$r->user()->shop_id,'status'=>$v['status']??'active'])); return response()->json($m,201);} 
  public function update(Request $r,Supplier $supplier): JsonResponse { abort_unless($supplier->shop_id===$r->user()->shop_id,403); $v=$r->validate(['name'=>'sometimes|required|string|max:150','contact_person'=>'nullable|string|max:150','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:150','address'=>'nullable|string','materials_supplied'=>'nullable|string','lead_time_days'=>'nullable|integer|min:0','notes'=>'nullable|string','status'=>'nullable|string|max:50']); $supplier->update($v); return response()->json($supplier->fresh()); }
}
