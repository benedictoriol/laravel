<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerSetting;
use App\Services\OwnerAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerSettingsController extends Controller
{
    public function __construct(protected OwnerAutomationService $automation) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner' && $user->shop_id, 403);
        $settings = $this->automation->bootstrapOwnerDefaults($user->shop);
        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner' && $user->shop_id, 403);

        $validated = $request->validate([
            'shop_name' => ['nullable','string','max:150'],
            'address' => ['nullable','string'],
            'contact_number' => ['nullable','string','max:50'],
            'contact_email' => ['nullable','email','max:150'],
            'operating_hours' => ['nullable','string','max:255'],
            'default_labor_rate' => ['nullable','numeric','min:0'],
            'rush_fee_percent' => ['nullable','numeric','min:0'],
            'default_profit_margin' => ['nullable','numeric','min:0'],
            'minimum_order_quantity' => ['nullable','integer','min:1'],
            'max_rush_orders_per_day' => ['nullable','integer','min:0'],
            'cancellation_rules' => ['nullable','string'],
            'notification_settings_json' => ['nullable','array'],
            'delivery_defaults_json' => ['nullable','array'],
            'ui_preferences_json' => ['nullable','array'],
            'security_settings_json' => ['nullable','array'],
            'workflow_automation_settings_json' => ['nullable','array'],
            'document_settings_json' => ['nullable','array'],
            'approval_settings_json' => ['nullable','array'],
        ]);

        $settings = OwnerSetting::firstOrCreate(['shop_id' => $user->shop_id]);
        $settings->fill($validated)->save();

        return response()->json($settings->fresh());
    }
}
