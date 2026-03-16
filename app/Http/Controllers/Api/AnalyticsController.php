<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DssRecommendation;
use App\Models\DssShopMetric;
use App\Models\Order;
use App\Models\Shop;
use App\Services\AnalyticsAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsAutomationService $analytics)
    {
    }

    public function shopMetrics(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && (int) ($user->shop_id ?? 0) !== (int) $shop->id) {
            abort(403, 'Unauthorized.');
        }

        return response()->json([
            'latest' => DssShopMetric::where('shop_id', $shop->id)->latest('metric_date')->first(),
            'history' => DssShopMetric::where('shop_id', $shop->id)->latest('metric_date')->limit(30)->get(),
        ]);
    }

    public function refreshShopMetrics(Request $request, Shop $shop): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            // allowed
        } elseif ($user->role === 'owner') {
            if ((int) ($user->shop_id ?? 0) !== (int) $shop->id) {
                abort(403, 'Unauthorized.');
            }
        } else {
            abort(403, 'Unauthorized.');
        }

        $metric = $this->analytics->refreshShopMetrics($shop->id, 'manual_refresh');

        return response()->json([
            'message' => 'Shop metrics refreshed successfully.',
            'metric' => $metric,
        ]);
    }

    public function orderRisk(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! ((int) $user->id === (int) $order->client_user_id || (int) ($user->shop_id ?? 0) === (int) $order->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        return response()->json($this->analytics->scanOrderRisk($order, 'manual_scan'));
    }

    public function recommendations(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'client') {
            return response()->json(
                DssRecommendation::with('shop')
                    ->where('generated_for_type', 'client')
                    ->where(function ($q) use ($user) {
                        $q->whereNull('client_user_id')
                        ->orWhere('client_user_id', $user->id);
                    })
                    ->orderBy('rank_position')
                    ->get()
            );
        }

        if ($user->role === 'admin') {
            return response()->json(
                DssRecommendation::with('shop')
                    ->orderBy('generated_at', 'desc')
                    ->orderBy('rank_position')
                    ->get()
            );
        }

        return response()->json([
            'message' => 'Recommendations endpoint is not available for this account.'
        ], 403);
    }
}
