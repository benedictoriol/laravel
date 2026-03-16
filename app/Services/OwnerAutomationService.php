<?php

namespace App\Services;

use App\Models\OperationalAlert;
use App\Models\OwnerSetting;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\ShopService;

class OwnerAutomationService
{
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
                'notification_settings_json' => [
                    'new_order' => true,
                    'delayed_production' => true,
                    'payment_received' => true,
                    'low_stock' => true,
                ],
                'delivery_defaults_json' => [
                    'preferred_courier' => 'LBC',
                    'pickup_hours' => '10:00 AM - 5:00 PM',
                    'shipping_fee_rules' => 'Actual courier rate or configured flat rate.',
                ],
                'ui_preferences_json' => [
                    'theme' => 'system',
                    'language' => 'en',
                    'dashboard_layout' => 'operations_first',
                ],
                'security_settings_json' => [
                    'device_access_review' => true,
                    'login_session_visibility' => true,
                ],
                'workflow_automation_settings_json' => [
                    'auto_move_order_after_payment' => true,
                    'auto_create_production_task' => true,
                    'auto_low_stock_alert' => true,
                    'auto_notify_owner_on_dispute' => true,
                    'auto_notify_client_on_proof_update' => true,
                ],
                'document_settings_json' => [
                    'invoice_format' => 'EMB-INV-{number}',
                    'quotation_format' => 'EMB-QUO-{number}',
                    'receipt_numbering' => 'EMB-REC-{number}',
                ],
                'approval_settings_json' => [
                    'discount_approver_role' => 'owner',
                    'dispute_approver_role' => 'owner',
                    'supplier_order_approver_role' => 'owner',
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
                OperationalAlert::firstOrCreate(
                    [
                        'shop_id' => $shopId,
                        'alert_type' => 'low_stock',
                        'related_model_type' => RawMaterial::class,
                        'related_model_id' => $material->id,
                    ],
                    [
                        'severity' => $material->stock_quantity <= 0 ? 'high' : 'medium',
                        'title' => 'Low stock material',
                        'message' => sprintf('%s is at %s %s.', $material->material_name, rtrim(rtrim((string) $material->stock_quantity, '0'), '.'), $material->unit),
                        'status' => 'open',
                    ]
                );
            }
        }
    }
}
