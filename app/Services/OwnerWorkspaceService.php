<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\DisputeCase;
use App\Models\MessageThread;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OwnerSetting;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Models\QualityCheck;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\ShopCourier;
use App\Models\ShopMember;
use App\Models\ShopProject;
use App\Models\ShopService;
use App\Models\Supplier;
use App\Models\SupportTicket;
use App\Models\SupplyOrder;
use App\Models\User;
use App\Models\WorkforceSchedule;
use Carbon\Carbon;

class OwnerWorkspaceService
{
    public function __construct(
        protected OwnerAutomationService $automation,
        protected PricingSuggestionService $pricing,
        protected ProductionOrchestrationService $production,
        protected AutomationTraceService $trace,
    ) {}

    public function build(Shop $shop): array
    {
        $settings = $this->automation->bootstrapOwnerDefaults($shop);
        $this->automation->syncLowStockAlerts($shop->id);
        $signals = $this->automation->refreshOperationalSignals($shop);

        $orders = Order::query()
            ->with([
                'client:id,name',
                'service:id,service_name',
                'assignments.assignee:id,name,role',
                'fulfillment',
                'payments',
                'progressLogs',
                'quotes',
            ])
            ->where('shop_id', $shop->id)
            ->latest('id')
            ->limit(120)
            ->get();

        $payments = Payment::query()->with(['order:id,order_number,current_stage,status,payment_due_date', 'client:id,name'])->where('shop_id', $shop->id)->latest('id')->limit(120)->get();
        $services = ShopService::query()->where('shop_id', $shop->id)->orderBy('service_name')->get();
        $suppliers = Supplier::query()->where('shop_id', $shop->id)->latest('id')->get();
        $materials = RawMaterial::query()->with('supplier:id,name')->where('shop_id', $shop->id)->latest('id')->get();
        $supplyOrders = SupplyOrder::query()->with('supplier:id,name')->where('shop_id', $shop->id)->latest('id')->get();
        $qualityChecks = QualityCheck::query()->with(['order:id,order_number,current_stage,status', 'checker:id,name'])->where('shop_id', $shop->id)->latest('id')->get();
        $projects = ShopProject::query()->where('shop_id', $shop->id)->latest('id')->get();
        $threads = MessageThread::query()->with(['messages.sender:id,name'])->where('shop_id', $shop->id)->latest('last_message_at')->limit(40)->get();
        $disputes = DisputeCase::query()->with(['order:id,order_number', 'complainant:id,name', 'handler:id,name'])->where('shop_id', $shop->id)->latest('id')->get();
        $alerts = OperationalAlert::query()->where('shop_id', $shop->id)->whereIn('status', ['open', 'snoozed'])->latest('id')->limit(60)->get();
        $staff = User::query()->where('shop_id', $shop->id)->whereIn('role', ['hr', 'staff'])->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']);
        $shopMembers = ShopMember::query()->with(['user:id,name,email,role,is_active', 'creator:id,name', 'reviewer:id,name'])->where('shop_id', $shop->id)->latest('id')->get();
        $clientCandidates = User::query()->where('role', 'client')->where(function ($query) use ($shop) { $query->whereNull('shop_id')->orWhere('shop_id', $shop->id); })->orderBy('name')->limit(120)->get(['id','name','email','shop_id']);
        $schedules = WorkforceSchedule::query()->with(['user:id,name,role','order:id,order_number,due_date'])->where('shop_id', $shop->id)->orderBy('shift_date')->limit(120)->get();
        $couriers = ShopCourier::query()->where('shop_id', $shop->id)->latest('id')->get();
        $supportTickets = SupportTicket::query()->with(['user:id,name', 'order:id,order_number,current_stage,status', 'assignee:id,name'])->where('shop_id', $shop->id)->latest('id')->limit(80)->get();

        $proofRequests = DesignCustomization::query()
            ->with(['user:id,name', 'designPost.selectedShop:id,shop_name', 'order:id,order_number,shop_id,deadline,due_date,status,current_stage', 'proofs.generator:id,name', 'proofs.responder:id,name', 'approvedProof', 'snapshots.actor:id,name', 'workflowEvents.actor:id,name', 'productionPackages.creator:id,name', 'latestProductionPackage'])
            ->where(function ($query) use ($shop) {
                $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('shop_id', $shop->id))
                    ->orWhereHas('designPost', fn ($postQuery) => $postQuery->where('selected_shop_id', $shop->id));
            })
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(function ($item) use ($services) {
                $service = $services->firstWhere('category', ($item->design_type ?? '')) ?? $services->first();
                $estimate = $this->pricing->estimate($item->toArray(), $service);
                $item->setAttribute('suggested_quote', $item->estimated_total_price ?: ($estimate['suggested_total'] ?? 0));
                $item->setAttribute('pricing_breakdown_preview', $item->pricing_breakdown_json ?: $estimate);
                $item->setAttribute('proof_history_count', $item->proofs->count());
                $item->setAttribute('revision_count', $item->workflowEvents->where('event_type', 'revision_requested')->count());
                $item->setAttribute('latest_activity', $item->workflowEvents->sortByDesc('id')->first());
                $item->setAttribute('production_package_count', $item->productionPackages->count());
                $item->setAttribute('risk_flag_count', count($item->risk_flags_json ?? []));
                $item->setAttribute('latest_package', $item->latestProductionPackage);
                return $item;
            });

        $confirmedPayments = $payments->where('payment_status', 'confirmed');
        $delayPredictions = $orders->map(fn (Order $order) => $this->production->scanOrderHealth($order))->values();
        $workforceRecommendations = $this->production->recommendWorkforce($shop->id);

        $shopUserIds = User::query()->where('shop_id', $shop->id)->pluck('id')->prepend($shop->owner_user_id)->filter()->unique()->values();
        $dedupedNotifications = $this->trace->normalizeNotifications($shopUserIds->all());
        $shopNotifications = PlatformNotification::query()->whereIn('user_id', $shopUserIds)->latest('id')->limit(250)->get();

        $materialIntelligence = $materials->map(function (RawMaterial $material) {
            $status = $material->stock_quantity <= 0
                ? 'critical'
                : (($material->reorder_level !== null && $material->stock_quantity <= $material->reorder_level) ? 'warning' : 'healthy');

            return [
                'id' => $material->id,
                'material_name' => $material->material_name,
                'category' => $material->category,
                'stock_quantity' => (float) $material->stock_quantity,
                'reorder_level' => (float) ($material->reorder_level ?? 0),
                'supplier_id' => $material->supplier_id,
                'supplier_name' => $material->supplier?->name,
                'status' => $status,
                'suggested_restock_quantity' => max(1, (((float) ($material->reorder_level ?? 0)) * 2) - (float) $material->stock_quantity),
            ];
        })->values();

        $paymentFollowups = $orders
            ->filter(fn (Order $order) => $order->approved_quote_id && $order->payment_status !== 'paid')
            ->map(function (Order $order) {
                $latestPayment = $order->payments->sortByDesc('id')->first();
                $dueDate = $order->payment_due_date;
                $overdue = $dueDate ? Carbon::parse($dueDate)->isPast() : false;
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'client' => $order->client?->name,
                    'payment_status' => $order->payment_status,
                    'amount_due' => round((float) max(0, (float) $order->total_amount - (float) $order->payments->where('payment_status', 'confirmed')->sum('amount')), 2),
                    'payment_due_date' => optional($dueDate)->toDateString(),
                    'overdue' => $overdue,
                    'latest_payment_status' => $latestPayment?->payment_status,
                    'recommended_action' => $overdue ? 'Follow up payment now' : 'Monitor payment window',
                ];
            })
            ->values();

        $productionBoard = [
            'pending_input' => ['label' => 'Pending input', 'items' => []],
            'digitizing' => ['label' => 'Digitizing', 'items' => []],
            'production' => ['label' => 'Embroidery', 'items' => []],
            'quality_control' => ['label' => 'Quality control', 'items' => []],
            'fulfillment' => ['label' => 'Fulfillment', 'items' => []],
            'done' => ['label' => 'Done', 'items' => []],
        ];

        foreach ($orders as $order) {
            $health = $delayPredictions->firstWhere('order_id', $order->id) ?? ['risk' => 'low', 'signals' => []];
            $stage = match (true) {
                in_array($order->status, ['pending', 'quoted', 'approved', 'awaiting_payment', 'payment_pending'], true) => 'pending_input',
                in_array($order->current_stage, ['digitizing', 'design_review'], true) => 'digitizing',
                in_array($order->current_stage, ['embroidery', 'stitching', 'assembly', 'rework', 'paused'], true) || $order->status === 'in_production' => 'production',
                in_array($order->current_stage, ['quality_control', 'ready_for_qc'], true) => 'quality_control',
                in_array($order->current_stage, ['ready_for_fulfillment', 'packaging', 'shipping', 'pickup_ready', 'delivered'], true) || in_array(optional($order->fulfillment)->status, ['scheduled', 'ready', 'shipped', 'delivered', 'picked_up'], true) => 'fulfillment',
                in_array($order->status, ['completed'], true) => 'done',
                default => 'production',
            };

            $blockers = [];
            if ($order->payment_status !== 'paid' && $order->approved_quote_id) {
                $blockers[] = 'Payment incomplete';
            }
            if ($health['risk'] !== 'low') {
                $blockers[] = implode(', ', $health['signals']);
            }
            if (in_array($stage, ['fulfillment', 'done'], true) === false && $materialIntelligence->whereIn('status', ['warning', 'critical'])->isNotEmpty()) {
                $blockers[] = 'Check material readiness';
            }

            $productionBoard[$stage]['items'][] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'client' => $order->client?->name,
                'current_stage' => $order->current_stage,
                'status' => $order->status,
                'risk' => $health['risk'],
                'assigned_staff' => $order->assignments->whereIn('status', ['assigned', 'in_progress'])->pluck('assignee.name')->filter()->values(),
                'due_date' => optional($order->due_date)->toDateString(),
                'blockers' => array_values(array_unique(array_filter($blockers))),
                'recommended_action' => $health['risk'] === 'high' ? 'Escalate order' : ($order->payment_status !== 'paid' && $order->approved_quote_id ? 'Follow up payment' : 'Review assignments'),
            ];
        }
        $productionBoard = collect($productionBoard)
            ->map(fn ($column) => array_merge($column, ['items' => collect($column['items'])->values()]))
            ->values();

        $restockRecommendations = $materialIntelligence->whereIn('status', ['warning', 'critical'])->values()->map(function ($row) use ($suppliers) {
            $supplier = $suppliers->firstWhere('id', $row['supplier_id']) ?? $suppliers->first();
            return array_merge($row, [
                'recommended_supplier_id' => $supplier?->id,
                'recommended_supplier_name' => $supplier?->name,
                'recommended_action' => 'Create restock request',
            ]);
        });

        $ordersAwaitingQc = $orders->filter(function (Order $order) use ($qualityChecks) {
            if (! in_array($order->current_stage, ['quality_control', 'ready_for_qc', 'ready_for_fulfillment'], true)) {
                return false;
            }
            return ! $qualityChecks->where('order_id', $order->id)->where('checked_at', '>=', now()->subDays(7))->count();
        })->values();

        $fulfillmentBoard = $orders->map(function (Order $order) {
            $fulfillment = $order->fulfillment;
            return [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->client?->name,
                'delivery_type' => $order->fulfillment_type,
                'status' => $fulfillment?->status ?? 'pending',
                'courier' => $fulfillment?->courier_name,
                'tracking_reference' => $fulfillment?->tracking_number,
                'next_action' => match ($fulfillment?->status) {
                    'pending', null => 'Mark package ready',
                    'ready' => 'Assign courier',
                    'scheduled' => 'Ship order',
                    'shipped' => 'Track delivery',
                    default => 'Monitor fulfillment',
                },
            ];
        })->values();

        $actionCenter = collect();
        foreach ($alerts as $alert) {
            $actionCenter->push([
                'type' => 'alert',
                'priority' => $alert->severity,
                'title' => $alert->title,
                'description' => $alert->message,
                'reference_id' => $alert->id,
                'reference_type' => 'alert',
                'suggested_action' => 'Resolve or snooze alert',
            ]);
        }
        foreach ($paymentFollowups->take(20) as $followup) {
            $actionCenter->push([
                'type' => 'payment_followup',
                'priority' => $followup['overdue'] ? 'high' : 'medium',
                'title' => 'Payment follow-up · '.$followup['order_number'],
                'description' => 'Outstanding amount: ₱ '.number_format($followup['amount_due'], 2),
                'reference_id' => $followup['order_id'],
                'reference_type' => 'order',
                'suggested_action' => 'Follow up payment',
            ]);
        }
        foreach ($restockRecommendations->take(20) as $restock) {
            $actionCenter->push([
                'type' => 'restock',
                'priority' => $restock['status'] === 'critical' ? 'critical' : 'medium',
                'title' => 'Restock '.$restock['material_name'],
                'description' => 'Suggested quantity: '.$restock['suggested_restock_quantity'].' '.$materials->firstWhere('id', $restock['id'])?->unit,
                'reference_id' => $restock['id'],
                'reference_type' => 'raw_material',
                'suggested_action' => 'Create restock request',
            ]);
        }
        foreach ($ordersAwaitingQc as $order) {
            $actionCenter->push([
                'type' => 'quality_check',
                'priority' => 'medium',
                'title' => 'Quality check pending · '.$order->order_number,
                'description' => 'Order is waiting for QC sign-off.',
                'reference_id' => $order->id,
                'reference_type' => 'order',
                'suggested_action' => 'Record quality check',
            ]);
        }

        $automationSettings = $settings->workflow_automation_settings_json ?: [];
        $governance = collect($automationSettings)->map(function ($enabled, $key) {
            return [
                'key' => $key,
                'enabled' => (bool) $enabled,
                'label' => ucwords(str_replace('_', ' ', preg_replace('/^auto_/', '', $key))),
                'explanation' => match ($key) {
                    'auto_move_order_after_payment' => 'Moves eligible orders into production after payment confirmation.',
                    'auto_create_production_task' => 'Creates execution tasks and stage records after orchestration.',
                    'auto_low_stock_alert' => 'Flags low stock before it blocks active work.',
                    'auto_notify_owner_on_dispute' => 'Creates attention alerts when disputes arrive.',
                    'auto_notify_client_on_proof_update' => 'Informs clients when proofs and quote updates are ready.',
                    'auto_predict_delays' => 'Scans order inactivity and due dates to predict delay risk.',
                    'auto_assign_staff' => 'Suggests or creates assignments based on active load.',
                    'auto_reserve_materials' => 'Reserves inventory automatically when production begins.',
                    default => 'Controls whether this automation remains active.',
                },
            ];
        })->values();

        $notificationsSummary = [
            'total' => $shopNotifications->count(),
            'unread' => $shopNotifications->where('is_read', false)->count(),
            'high_priority' => $shopNotifications->whereIn('priority', ['high', 'critical'])->count(),
            'deduplicated' => $dedupedNotifications,
            'by_category' => $shopNotifications->countBy('category'),
        ];

        $acceptedQuotes = OrderQuote::query()->where('shop_id', $shop->id)->where('status', 'accepted')->get();
        $rejectedQuotes = OrderQuote::query()->where('shop_id', $shop->id)->where('status', 'rejected')->get();
        $hasPricingInsights = $acceptedQuotes->count() + $rejectedQuotes->count() >= 3;
        $pricingRules = $settings->pricing_rules_json ?? [];
        $quoteAutomationControls = array_merge([
            'use_system_suggested_price' => true,
            'allow_owner_override' => true,
            'auto_add_labor_estimate' => true,
            'auto_add_material_estimate' => true,
            'auto_add_rush_fee' => true,
            'auto_add_shipping_estimate' => false,
        ], $settings->quote_automation_controls_json ?? []);

        $recentOrders = $orders->take(8)->values();
        $pendingStatuses = ['pending', 'quoted', 'approved'];
        $activeStatuses = ['in_production', 'ready_for_pickup', 'shipped', 'on_hold'];

        return [
            'shop' => $shop,
            'settings' => $settings,
            'overview' => [
                'stats' => [
                    'total_orders' => $orders->count(),
                    'pending_orders' => $orders->whereIn('status', $pendingStatuses)->count(),
                    'active_orders' => $orders->whereIn('status', $activeStatuses)->count(),
                    'completed_orders' => $orders->where('status', 'completed')->count(),
                    'total_earnings' => round((float) $confirmedPayments->sum('amount'), 2),
                    'low_stock_materials' => $materialIntelligence->whereIn('status', ['warning', 'critical'])->count(),
                    'urgent_alerts' => $alerts->whereIn('severity', ['high', 'critical'])->count(),
                    'delay_predictions' => $delayPredictions->where('risk', 'high')->count(),
                    'unpaid_accepted_quotes' => $paymentFollowups->count(),
                    'qc_pending' => $ordersAwaitingQc->count(),
                ],
                'recent_orders' => $recentOrders,
                'alerts' => $alerts,
                'pending_proof_requests' => $proofRequests->whereIn('status', ['draft', 'submitted', 'pricing_ready', 'proof_ready'])->values()->take(8),
                'low_stock_items' => $restockRecommendations->take(8)->values(),
                'action_center' => $actionCenter->sortByDesc(fn ($item) => ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4][$item['priority']] ?? 0)->values()->take(20),
            ],
            'orders' => $orders,
            'design_proofing' => $proofRequests,
            'pricing' => $services,
            'pricing_control_center' => [
                'services' => $services,
                'rules' => $pricingRules,
                'automation_controls' => $quoteAutomationControls,
                'minimum_order_quantity' => $settings->minimum_order_quantity,
                'minimum_billable_amount' => $settings->minimum_billable_amount,
                'max_manual_discount_percent' => $settings->max_manual_discount_percent,
                'insights' => $hasPricingInsights ? [
                    'average_accepted_quote_price' => round((float) $acceptedQuotes->avg('total_amount'), 2),
                    'most_common_service_price' => round((float) (($services->mode('base_price')[0] ?? null) ?: ($services->avg('base_price') ?? 0)), 2),
                    'rejected_quote_count' => $rejectedQuotes->count(),
                    'discount_usage_rate' => round(($acceptedQuotes->where('discount_amount', '>', 0)->count() / max(1, $acceptedQuotes->count())) * 100, 2),
                ] : null,
            ],
            'payments' => $payments,
            'payment_followups' => $paymentFollowups,
            'earnings' => [
                'summary' => [
                    'today' => round((float) $confirmedPayments->filter(fn ($payment) => optional($payment->paid_at)->isSameDay(now()))->sum('amount'), 2),
                    'week' => round((float) $confirmedPayments->filter(fn ($payment) => optional($payment->paid_at) && $payment->paid_at->greaterThanOrEqualTo(now()->copy()->startOfWeek()))->sum('amount'), 2),
                    'month' => round((float) $confirmedPayments->filter(fn ($payment) => optional($payment->paid_at) && $payment->paid_at->greaterThanOrEqualTo(now()->copy()->startOfMonth()))->sum('amount'), 2),
                    'year' => round((float) $confirmedPayments->filter(fn ($payment) => optional($payment->paid_at) && $payment->paid_at->greaterThanOrEqualTo(now()->copy()->startOfYear()))->sum('amount'), 2),
                ],
            ],
            'production_tracking' => $orders->map(fn (Order $order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'client' => $order->client?->name,
                'assigned_staff' => $order->assignments->last()?->assignee?->name,
                'stage' => $order->current_stage,
                'priority' => $delayPredictions->firstWhere('order_id', $order->id)['risk'] ?? 'low',
                'due_date' => $order->due_date,
                'status' => $order->status,
                'last_update' => $order->updated_at,
            ])->values(),
            'production_board' => $productionBoard,
            'quality_control' => $qualityChecks,
            'quality_queue' => $ordersAwaitingQc,
            'projects' => $projects,
            'supplier_management' => $suppliers,
            'raw_materials' => $materials,
            'restock_recommendations' => $restockRecommendations,
            'supply_chain' => $supplyOrders,
            'staff' => $staff,
            'staff_directory' => $shopMembers,
            'staff_candidates' => $clientCandidates,
            'operations' => $staff->map(function (User $member) {
                $assignments = $member->assignedOrderAssignments;
                return [
                    'staff_id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role,
                    'active_tasks' => $assignments->whereIn('status', ['assigned', 'in_progress'])->count(),
                    'completed_tasks' => $assignments->where('status', 'done')->count(),
                    'delayed_tasks' => $assignments->filter(fn ($assignment) => $assignment->assigned_at && $assignment->assigned_at->lt(now()->subDays(3)) && $assignment->status !== 'done')->count(),
                ];
            })->values(),
            'workforce_scheduling' => $schedules,
            'couriers' => $couriers,
            'delivery_pickup' => $fulfillmentBoard,
            'dispute_resolution' => $disputes,
            'messages' => $threads,
            'support_tickets' => $supportTickets,
            'marketplace' => [
                'client_design_requests' => \App\Models\DesignPost::query()
                    ->with(['client:id,name', 'selectedShop:id,shop_name', 'applications.shop', 'applications.owner'])
                    ->where(function ($query) use ($shop) {
                        $query->where('visibility', 'public')
                            ->orWhere('selected_shop_id', $shop->id)
                            ->orWhereHas('applications', fn ($appQuery) => $appQuery->where('shop_id', $shop->id));
                    })
                    ->latest('id')
                    ->limit(50)
                    ->get(),
            ],
            'analytics' => [
                'cards' => [
                    'quote_conversion_rate' => $proofRequests->count() ? round(($orders->whereNotNull('approved_quote_id')->count() / max(1, $proofRequests->count())) * 100, 2) : 0,
                    'revision_frequency' => round($orders->avg(fn (Order $order) => $order->progressLogs->where('status', 'revision_requested')->count()), 2),
                    'delayed_order_rate' => $orders->count() ? round(($delayPredictions->where('risk', 'high')->count() / max(1, $orders->count())) * 100, 2) : 0,
                    'payment_completion_rate' => $orders->count() ? round(($orders->where('payment_status', 'paid')->count() / max(1, $orders->count())) * 100, 2) : 0,
                    'average_staff_load' => round($staff->avg(fn (User $member) => $member->assignedOrderAssignments->whereIn('status', ['assigned', 'in_progress'])->count()) ?? 0, 2),
                    'qc_pass_rate' => $qualityChecks->count() ? round(($qualityChecks->where('rework_required', false)->count() / max(1, $qualityChecks->count())) * 100, 2) : 0,
                ],
            ],
            'automation' => [
                'delay_predictions' => $delayPredictions,
                'workforce_recommendations' => $workforceRecommendations,
                'material_intelligence' => $materialIntelligence,
                'support_queue' => $supportTickets->whereNotIn('status', ['resolved', 'closed'])->values(),
                'governance' => $governance,
                'notification_lifecycle' => $notificationsSummary,
                'signals' => $signals,
            ],
        ];
    }
}
