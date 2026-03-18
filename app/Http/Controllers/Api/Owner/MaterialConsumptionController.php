<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\MaterialConsumption;
use App\Services\MaterialConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialConsumptionController extends Controller
{
    public function __construct(protected MaterialConsumptionService $consumption)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->consumption->listForShop($request->user()->shop));
    }

    public function update(Request $request, MaterialConsumption $materialConsumption): JsonResponse
    {
        $validated = $request->validate([
            'actual_quantity' => ['required', 'numeric', 'min:0'],
            'owner_note' => ['nullable', 'string'],
            'adjustment_reason' => ['nullable', 'string'],
        ]);

        return response()->json([
            'message' => 'Material consumption updated.',
            'consumption' => $this->consumption->updateActual($request->user()->shop, $materialConsumption, $request->user(), $validated),
        ]);
    }
}
