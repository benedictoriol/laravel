<?php

namespace App\Services;

use App\Models\DssShopMetric;
use App\Models\Order;
use App\Models\OwnerSetting;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\ShopService;

class OwnerAutomationService
{
    public function __construct(
        protected AutomationTraceService $trace,
        protected ProductionOrchestrationService $production,
    ) {}

    public function bootstrapOwnerDefaults(Shop $shop): OwnerSetting
    {
        $settings = OwnerSetting::firstOrCreate(
            ['shop_id' => $shop->id],
            [
                'shop_name' => $shop->shop_name ?? 'Embroidery Shop',
                'address' => $shop->address ?? null,
                'contact_number' => $shop->contact_number ?? null,
                'contact_email' => $shop->contact_email ?? null,
                'operating_hours' => $shop->operating_hours ?? 'Mon-Sat 9:00 AM - 6:00 PM',
                'default_labor_rate' => 35,
                'rush_fee_percent' => 15,
                'default_profit_margin' => 20,
                'minimum_order_quantity' => 1,
                'max_rush_orders_per_day' => 5,
                'cancellation_rules' => 'Rush orders are non-refundable once production starts.',
                'workflow_automation_settings_json' => [
                    'auto_move_order_after_payment' => true,
                    'auto_create_production_task' => true,
                    'auto_low_stock_alert' => true,
                    'auto_notify_owner_on_dispute' => true,
                    'auto_notify_client_on_proof_update' => true,
                    'auto_predict_delays' => true,
                    'auto_assign_staff' => true,
                    'auto_reserve_materials' => true,
                    'auto_payment_follow_up' => true,
                    'auto_notification_maintenance' => true,
                    'auto_quality_gate' => true,
                    'auto_fulfillment_updates' => true,
                ],
            ]
        );

        $defaults = [
            ['service_name' => 'Logo embroidery', 'category' => 'logo_embroidery', 'base_price' => 120, 'min_order_qty' => 1, 'unit_price' => 120],
            ['service_name' => 'Name embroidery', 'category' => 'name_embroidery', 'base_price' => 60, 'min_order_qty' => 1, 'unit_price' => 60],
            ['service_name' => 'Patch embroidery', 'category' => 'patch_embroidery', 'base_price' => 90, 'min_order_qty' => 5, 'unit_price' => 90],
            ['service_name' => 'Uniform embroidery', 'category' => 'uniform_embroidery', 'base_price' => 140, 'min_order_qty' => 5, 'unit_price' => 140],
            ['service_name' => 'Cap embroidery', 'category' => 'cap_embroidery', 'base_price' => 135, 'min_order_qty' => 5, 'unit_price' => 135],
            ['service_name' => 'Custom design embroidery', 'category' => 'custom_design_embroidery', 'base_price' => 180, 'min_order_qty' => 1, 'unit_price' => 180],
        ];

        foreach ($defaults as $row) {
            ShopService::firstOrCreate(
                ['shop_id' => $shop->id, 'service_name' => $row['service_name']],
                array_merge($row, [
                    'price_type' => 'quoted',
                    'turnaround_days' => 3,
                    'is_active' => true,
                    'stitch_range' => '1500-5000',
                    'complexity_multiplier' => 1.00,
                    'rush_fee_allowed' => true,
                ])
            );
        }

        return $settings;
    }

    public function syncLowStockAlerts(int $shopId): void
    {
        $materials = RawMaterial::query()->where('shop_id', $shopId)->get();
        foreach ($materials as $material) {
            if ($material->reorder_level !== null && $material->stock_quantity <= $material->reorder_level) {
                $this->trace->alertOnce(
                    $shopId,
                    null,
                    'low_stock',
                    $material->stock_quantity <= 0 ? 'critical' : 'medium',
                    'Low stock material',
                    sprintf('%s is at %s %s.', $material->material_name, rtrim(rtrim((string) $material->stock_quantity, '0'), '.'), $material->unit),
                    RawMaterial::class,
                    $material->id,
                    ['material_name' => $material->material_name]
                );
            }
        }
    }

    public function refreshOperationalSignals(Shop $shop): array
    {
        $orders = Order::query()->where('shop_id', $shop->id)->whereNotIn('status', ['completed', 'cancelled', 'rejected'])->get();
        $predictions = $orders->map(fn (Order $order) => $this->production->scanOrderHealth($order));

        $today = now()->toDateString();
        DssShopMetric::updateOrCreate(
            ['shop_id' => $shop->id, 'metric_date' => $today],
            [
                'total_orders' => Order::query()->where('shop_id', $shop->id)->count(),
                'completed_orders' => Order::query()->where('shop_id', $shop->id)->where('status', 'completed')->count(),
                'cancelled_orders' => Order::query()->where('shop_id', $shop->id)->where('status', 'cancelled')->count(),
                'avg_rating' => (float) ($shop->reviews()->avg('rating') ?? 0),
                'review_count' => $shop->reviews()->count(),
                'completion_rate' => $shop->orders()->count() ? ($shop->orders()->where('status', 'completed')->count() / max(1, $shop->orders()->count())) : 0,
                'avg_turnaround_days' => 3.5,
                'active_staff_count' => $shop->members()->count(),
                'open_job_posts_taken' => 0,
                'revenue_total' => (float) $shop->orders()->sum('total_amount'),
                'price_competitiveness_score' => 75,
                'recommendation_score' => 80,
                'delay_risk_score' => $predictions->where('risk', 'high')->count() > 0 ? 70 : 20,
            ]
        );

        return [
            'predictions' => $predictions->values()->all(),
            'high_risk_count' => $predictions->where('risk', 'high')->count(),
        ];
    }
}
