<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\DesignCustomizationSnapshot;
use App\Models\DesignPost;
use App\Models\EmbroideryDesignSession;
use App\Models\EmbroideryDesignVersion;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Models\Shop;
use App\Models\ShopService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientJourneyService
{
    public function __construct(private PricingSuggestionService $pricingSuggestionService)
    {
    }

    public function submitQuoteRequest(User $user, array $payload): array
    {
        $shop = Shop::query()->where('id', $payload['shop_id'])->where('verification_status', 'approved')->firstOrFail();
        $service = ShopService::query()
            ->where('shop_id', $shop->id)
            ->where('category', $payload['service_selection'])
            ->where('is_active', true)
            ->first();

        $estimate = $this->pricingSuggestionService->estimate([
            'quantity' => $payload['quantity'],
            'stitch_count_estimate' => $payload['stitch_count_estimate'],
            'color_count' => $payload['color_count'],
            'complexity_level' => $payload['complexity_level'],
            'width_mm' => $payload['width_mm'],
            'height_mm' => $payload['height_mm'],
            'design_type' => $payload['service_selection'],
            'placement_area' => $payload['placement_area'],
            'fabric_type' => $payload['fabric_type'],
            'is_rush' => $payload['is_rush'] ?? false,
        ], $service);

        return DB::transaction(function () use ($user, $payload, $shop, $service, $estimate) {
            $session = EmbroideryDesignSession::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'name' => $payload['name'],
                'garment_type' => $payload['garment_type'],
                'placement_area' => $payload['placement_area'],
                'canvas_width' => (int) round($payload['width_mm'] * 8),
                'canvas_height' => (int) round($payload['height_mm'] * 8),
                'thread_palette_json' => $payload['thread_palette_json'],
                'design_json' => $payload['design_json'],
                'preview_svg' => $payload['preview_svg'] ?? null,
                'estimated_stitches' => $estimate['stitch_count_estimate'],
                'thread_color_count' => $estimate['color_count'],
                'suggested_price' => $estimate['suggested_total'],
                'pricing_confidence' => $estimate['confidence_score'],
                'status' => 'proof_requested',
                'version_no' => 1,
                'last_priced_at' => now(),
            ]);

            EmbroideryDesignVersion::create([
                'session_id' => $session->id,
                'created_by' => $user->id,
                'version_no' => 1,
                'design_json' => $payload['design_json'],
                'preview_svg' => $payload['preview_svg'] ?? null,
                'estimated_stitches' => $estimate['stitch_count_estimate'],
                'thread_color_count' => $estimate['color_count'],
                'suggested_price' => $estimate['suggested_total'],
                'pricing_confidence' => $estimate['confidence_score'],
                'notes' => 'Initial client quote request package',
            ]);

            $designPost = DesignPost::create([
                'client_user_id' => $user->id,
                'selected_shop_id' => $shop->id,
                'title' => $payload['name'].' quote request',
                'description' => $payload['description'] ?: 'Client requested design proofing and price quotation.',
                'design_type' => $this->normalizeDesignType($payload['service_selection']),
                'fabric_type' => $payload['fabric_type'],
                'garment_type' => $payload['garment_type'],
                'quantity' => $payload['quantity'],
                'target_budget' => $estimate['suggested_total'],
                'visibility' => 'private',
                'status' => 'shop_selected',
                'reference_file_path' => $payload['upload_design_file'] ?: null,
                'notes' => $payload['description'],
            ]);

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'client_user_id' => $user->id,
                'shop_id' => $shop->id,
                'source_design_post_id' => $designPost->id,
                'service_id' => $service?->id,
                'order_type' => 'custom_order',
                'status' => 'pending',
                'current_stage' => 'quotation',
                'payment_status' => 'unpaid',
                'fulfillment_type' => 'delivery',
                'subtotal' => $estimate['subtotal'],
                'customization_fee' => $estimate['digitizing_fee'] + $estimate['material_fee'],
                'rush_fee' => $estimate['rush_fee'],
                'discount_amount' => 0,
                'total_amount' => $estimate['suggested_total'],
                'delivery_address' => $payload['delivery_address'] ?: null,
                'customer_notes' => $payload['description'],
                'payment_due_date' => now()->addDays(3)->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

            $designPost->update(['converted_order_id' => $order->id]);
            $session->update(['order_id' => $order->id]);

            OrderItem::create([
                'order_id' => $order->id,
                'item_name' => $payload['name'],
                'garment_type' => $payload['garment_type'],
                'fabric_type' => $payload['fabric_type'],
                'placement_area' => $payload['placement_area'],
                'quantity' => $payload['quantity'],
                'unit_price' => round(((float) $estimate['suggested_total']) / max((int) $payload['quantity'], 1), 2),
                'line_total' => $estimate['suggested_total'],
                'width_mm' => $payload['width_mm'],
                'height_mm' => $payload['height_mm'],
                'stitch_count' => $estimate['stitch_count_estimate'],
                'thread_colors' => $estimate['color_count'],
                'customization_notes' => $payload['description'],
                'mockup_approved' => false,
            ]);

            $customization = DesignCustomization::create([
                'design_post_id' => $designPost->id,
                'order_id' => $order->id,
                'user_id' => $user->id,
                'name' => $payload['name'],
                'garment_type' => $payload['garment_type'],
                'placement_area' => $payload['placement_area'],
                'fabric_type' => $payload['fabric_type'],
                'width_mm' => $payload['width_mm'],
                'height_mm' => $payload['height_mm'],
                'color_count' => $estimate['color_count'],
                'stitch_count_estimate' => $estimate['stitch_count_estimate'],
                'complexity_level' => $payload['complexity_level'],
                'notes' => $payload['description'],
                'artwork_path' => $payload['upload_design_file'] ?: null,
                'status' => 'estimated',
                'estimated_base_price' => $estimate['base_unit_price'],
                'estimated_total_price' => $estimate['suggested_total'],
                'pricing_breakdown_json' => $estimate,
                'design_session_json' => array_merge($payload['design_json'], [
                    'session_id' => $session->id,
                    'service_selection' => $payload['service_selection'],
                    'quantity' => $payload['quantity'],
                ]),
                'preview_meta_json' => [
                    'service_selection' => $payload['service_selection'],
                    'suggested_total' => $estimate['suggested_total'],
                    'shop_name' => $shop->shop_name,
                    'estimated_turnaround_days' => $service?->turnaround_days,
                ],
                'pricing_confidence_score' => $estimate['confidence_score'],
                'pricing_strategy' => $estimate['pricing_strategy'],
                'last_priced_at' => now(),
            ]);

            DesignCustomizationSnapshot::create([
                'design_customization_id' => $customization->id,
                'version_no' => 1,
                'captured_by' => $user->id,
                'change_summary' => 'Initial unified quote request submission',
                'snapshot_json' => $customization->only([
                    'name','garment_type','placement_area','fabric_type','width_mm','height_mm','color_count','stitch_count_estimate','complexity_level','notes','artwork_path','status','design_session_json','preview_meta_json'
                ]),
                'pricing_snapshot_json' => $estimate,
            ]);

            OrderStageHistory::create([
                'order_id' => $order->id,
                'stage_code' => 'quotation',
                'stage_status' => 'active',
                'started_at' => now(),
                'actor_user_id' => $user->id,
                'notes' => 'Auto-created from client unified quote request.',
            ]);

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'quote_requested',
                'title' => 'Quote request submitted',
                'description' => 'Client submitted a design proofing and quotation request to '.$shop->shop_name.'.',
                'actor_user_id' => $user->id,
            ]);

            PlatformNotification::create([
                'user_id' => $user->id,
                'type' => 'quote_request_submitted',
                'category' => 'quotes',
                'priority' => 'medium',
                'title' => 'Quote request submitted',
                'message' => 'Your design “'.$payload['name'].'” was sent to '.$shop->shop_name.' for proofing and quotation.',
                'action_label' => 'View request',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);

            if ($shop->owner_user_id) {
                PlatformNotification::create([
                    'user_id' => $shop->owner_user_id,
                    'type' => 'quote_request_received',
                    'category' => 'quotes',
                    'priority' => 'high',
                    'title' => 'New quote packet received',
                    'message' => 'A new quote packet for “'.$payload['name'].'” is waiting for review.',
                    'action_label' => 'View quote',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'channel' => 'web',
                ]);
            }

            return [
                'session' => $session,
                'design_post' => $designPost,
                'design_customization' => $customization->fresh(['designPost.selectedShop', 'proofs', 'approvedProof']),
                'order' => $order->fresh(['shop', 'service', 'items', 'fulfillment']),
                'pricing' => $estimate,
            ];
        });
    }

    private function normalizeDesignType(string $category): string
    {
        return match ($category) {
            'logo_embroidery' => 'logo',
            'patch_embroidery' => 'patch',
            'uniform_embroidery' => 'uniform',
            'cap_embroidery' => 'cap',
            default => 'custom_art',
        };
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
