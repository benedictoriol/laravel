<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationalAlert;
use App\Models\Shop;
use App\Services\SmartOpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmartOpsController extends Controller
{
    public function __construct(private SmartOpsService $smartOpsService)
    {
    }

    public function summary(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || (($user->isOwner() || $user->isHr()) && (int) ($user->shop_id ?? 0) === (int) $shop->id), 403);

        return response()->json($this->smartOpsService->shopSummary($shop->id));
    }

    public function scan(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isOwner() && (int) ($user->shop_id ?? 0) === (int) $shop->id), 403);

        $alerts = $this->smartOpsService->scanShop($shop->id)->values();

        return response()->json([
            'message' => 'Operational scan completed.',
            'created_alerts' => $alerts,
            'summary' => $this->smartOpsService->shopSummary($shop->id),
        ]);
    }

    public function resolve(Request $request, OperationalAlert $alert): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || (($user->isOwner() || $user->isHr()) && (int) ($user->shop_id ?? 0) === (int) ($alert->shop_id ?? 0)), 403);

        return response()->json($this->smartOpsService->resolveAlert($alert));
    }
}
