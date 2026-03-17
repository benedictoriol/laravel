<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerPricingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner' && $user->shop_id, 403);

        $settings = OwnerSetting::firstOrCreate(['shop_id' => $user->shop_id]);

        return response()->json([
            'pricing_rules_json' => $settings->pricing_rules_json ?? [],
            'quote_automation_controls_json' => $settings->quote_automation_controls_json ?? [],
            'minimum_order_quantity' => $settings->minimum_order_quantity,
            'minimum_billable_amount' => $settings->minimum_billable_amount,
            'max_manual_discount_percent' => $settings->max_manual_discount_percent,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner' && $user->shop_id, 403);

        $validated = $request->validate([
            'pricing_rules_json' => ['nullable', 'array'],
            'quote_automation_controls_json' => ['nullable', 'array'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'minimum_billable_amount' => ['nullable', 'numeric', 'min:0'],
            'max_manual_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $settings = OwnerSetting::firstOrCreate(['shop_id' => $user->shop_id]);
        $settings->fill($validated)->save();

        return response()->json($settings->fresh());
    }
}
