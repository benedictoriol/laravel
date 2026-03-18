<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\RawMaterial;
use App\Services\OwnerAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function __construct(protected OwnerAutomationService $automation) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            RawMaterial::with('supplier:id,name')
                ->where('shop_id', $request->user()->shop_id)
                ->latest('id')
                ->get()
                ->map(fn (RawMaterial $material) => $this->transform($material))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $material = RawMaterial::create($this->payload($request, false) + [
            'shop_id' => $request->user()->shop_id,
        ]);

        $material->update(['stock_status' => $material->refreshStockStatus()]);
        $this->automation->syncLowStockAlerts($request->user()->shop_id);

        return response()->json($this->transform($material->fresh()->load('supplier:id,name')), 201);
    }

    public function update(Request $request, RawMaterial $rawMaterial): JsonResponse
    {
        abort_unless($rawMaterial->shop_id === $request->user()->shop_id, 403);

        $rawMaterial->update($this->payload($request, true));
        $rawMaterial->update(['stock_status' => $rawMaterial->refreshStockStatus()]);
        $this->automation->syncLowStockAlerts($request->user()->shop_id);

        return response()->json($this->transform($rawMaterial->fresh()->load('supplier:id,name')));
    }

    protected function payload(Request $request, bool $partial): array
    {
        $rules = [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'material_name' => ($partial ? 'sometimes|' : '').'required|string|max:150',
            'material_code' => 'nullable|string|max:100',
            'sku' => 'nullable|string|max:100',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'stock_quantity' => 'nullable|numeric|min:0',
            'reserved_quantity' => 'nullable|numeric|min:0',
            'minimum_stock_level' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'reorder_threshold' => 'nullable|numeric|min:0',
            'maximum_stock_capacity' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'unit_purchase_cost' => 'nullable|numeric|min:0',
            'latest_cost' => 'nullable|numeric|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'selling_cost_contribution' => 'nullable|numeric|min:0',
            'estimated_usage_per_order_unit' => 'nullable|numeric|min:0',
            'usage_measurement' => 'nullable|string|max:80',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_code' => 'nullable|string|max:100',
            'preferred_supplier' => 'nullable|boolean',
            'thread_color' => 'nullable|string|max:100',
            'thread_type' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'thickness' => 'nullable|string|max:100',
            'fabric_type' => 'nullable|string|max:100',
            'fabric_color' => 'nullable|string|max:100',
            'texture' => 'nullable|string|max:100',
            'backing_type' => 'nullable|string|max:100',
            'weight' => 'nullable|string|max:100',
            'last_restocked_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];

        $validated = $request->validate($rules);
        $validated['unit'] = $validated['unit'] ?? 'pcs';
        $validated['preferred_supplier'] = (bool) ($validated['preferred_supplier'] ?? false);
        $validated['reorder_level'] = $validated['reorder_level'] ?? ($validated['minimum_stock_level'] ?? 0);
        $validated['reorder_threshold'] = $validated['reorder_threshold'] ?? ($validated['reorder_level'] ?? 0);
        $validated['cost_per_unit'] = $validated['cost_per_unit'] ?? ($validated['latest_cost'] ?? $validated['unit_purchase_cost'] ?? 0);
        $validated['stock_status'] = 'in_stock';
        $validated['status'] = 'active';

        return $validated;
    }

    protected function transform(RawMaterial $material): array
    {
        return array_merge($material->toArray(), [
            'supplier_name_display' => $material->supplier?->name ?? $material->supplier_name,
            'available_stock' => $material->available_stock,
            'stock_status' => $material->stock_status ?: $material->refreshStockStatus(),
        ]);
    }
}
