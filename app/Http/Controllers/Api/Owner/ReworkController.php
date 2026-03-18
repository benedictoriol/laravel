<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRework;
use App\Services\ReworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReworkController extends Controller
{
    public function __construct(protected ReworkService $reworks) {}

    public function index(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;

        $reworks = OrderRework::query()
            ->with([
                'order:id,order_number,current_stage,status',
                'qualityCheck:id,order_id,qc_status,defect_type,failed_at',
                'design:id,name',
                'productionPackage:id,package_no,status',
                'opener:id,name',
                'updater:id,name',
            ])
            ->where('shop_id', $shop->id)
            ->latest('id')
            ->get();

        return response()->json($reworks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'quality_check_id' => ['nullable', 'integer', 'exists:quality_checks,id'],
            'reason' => ['required', 'string'],
            'severity' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:rework_open,rework_in_progress,rework_done,rework_recheck,rework_closed'],
            'internal_note' => ['nullable', 'string'],
            'progress_notes' => ['nullable', 'string'],
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $rework = $this->reworks->openManual($request->user()->shop, $order, $request->user(), $validated);

        return response()->json([
            'message' => 'Rework opened successfully.',
            'rework' => $rework,
        ], 201);
    }

    public function update(Request $request, OrderRework $rework): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
            'severity' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:rework_open,rework_in_progress,rework_done,rework_recheck,rework_closed'],
            'internal_note' => ['nullable', 'string'],
            'progress_notes' => ['nullable', 'string'],
        ]);

        $rework = $this->reworks->update($request->user()->shop, $rework, $request->user(), $validated);

        return response()->json([
            'message' => 'Rework updated successfully.',
            'rework' => $rework,
        ]);
    }
}
