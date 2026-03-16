<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\DisputeCase;
use App\Models\MessageThread;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\OwnerSetting;
use App\Models\Payment;
use App\Models\QualityCheck;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\ShopProject;
use App\Models\ShopService;
use App\Models\SupplyOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkforceSchedule;
use App\Services\PricingSuggestionService;
use Carbon\Carbon;

class OwnerWorkspaceService
{
    public function __construct(
        protected OwnerAutomationService $automation,
        protected PricingSuggestionService $pricing
    ) {}

    public function build(Shop $shop): array
    {
        $this->automation->bootstrapOwnerDefaults($shop);
        $this->automation->syncLowStockAlerts($shop->id);

        $ordersQuery = Order::query()->with(['client:id,name', 'service:id,service_name'])->where('shop_id', $shop->id);
        $orders = (clone $ordersQuery)->latest('id')->limit(100)->get();
        $recentOrders = $orders->take(8)->values();
        $pendingStatuses = ['pending', 'quoted', 'awaiting_payment', 'payment_pending'];
        $activeStatuses = ['accepted', 'in_progress', 'production', 'ready_for_qc', 'ready_for_delivery'];

        $payments = Payment::query()->with(['order:id,order_number', 'client:id,name'])->where('shop_id', $shop->id)->latest('id')->limit(100)->get();
        $services = ShopService::query()->where('shop_id', $shop->id)->orderBy('service_name')->get();
        $suppliers = Supplier::query()->where('shop_id', $shop->id)->latest('id')->get();
        $materials = RawMaterial::query()->with('supplier:id,name')->where('shop_id', $shop->id)->latest('id')->get();
        $supplyOrders = SupplyOrder::query()->with('supplier:id,name')->where('shop_id', $shop->id)->latest('id')->get();
        $qualityChecks = QualityCheck::query()->with(['order:id,order_number', 'checker:id,name'])->where('shop_id', $shop->id)->latest('id')->get();
        $projects = ShopProject::query()->where('shop_id', $shop->id)->latest('id')->get();
        $threads = MessageThread::query()->with(['messages.sender:id,name'])->where('shop_id', $shop->id)->latest('last_message_at')->limit(40)->get();
        $disputes = DisputeCase::query()->with(['order:id,order_number', 'complainant:id,name', 'handler:id,name'])->where('shop_id', $shop->id)->latest('id')->get();
        $alerts = OperationalAlert::query()->where('shop_id', $shop->id)->where('status', 'open')->latest('id')->limit(30)->get();
        $staff = User::query()->where('shop_id', $shop->id)->whereIn('role', ['hr', 'staff'])->orderBy('name')->get(['id','name','email','role','is_active']);
        $schedules = WorkforceSchedule::query()->with('user:id,name,role')->where('shop_id', $shop->id)->orderByDesc('shift_date')->limit(60)->get();
        $proofRequests = DesignCustomization::query()->with(['user:id,name', 'order:id,order_number'])
            ->whereHas('order', fn($q) => $q->where('shop_id', $shop->id))
            ->latest('id')->limit(60)->get();

        $proofRequests->transform(function ($item) use ($services) {
            if (!$item->estimated_total_price) {
                $service = $services->firstWhere('category', ($item->design_type ?? '')) ?? $services->first();
                $estimate = $this->pricing->estimate($item->toArray(), $service);
                $item->setAttribute('suggested_quote', $estimate['suggested_total']);
                $item->setAttribute('pricing_breakdown_preview', $estimate);
            } else {
                $item->setAttribute('suggested_quote', $item->estimated_total_price);
                $item->setAttribute('pricing_breakdown_preview', $item->pricing_breakdown_json);
            }
            return $item;
        });

        $now = Carbon::now();
        $todayRevenue = (clone $payments)->where('payment_status', 'confirmed')->filter(fn($p) => optional($p->paid_at)->isSameDay($now))->sum('amount');
        $weekRevenue = (clone $payments)->where('payment_status', 'confirmed')->filter(fn($p) => optional($p->paid_at) && $p->paid_at->greaterThanOrEqualTo($now->copy()->startOfWeek()))->sum('amount');
        $monthRevenue = (clone $payments)->where('payment_status', 'confirmed')->filter(fn($p) => optional($p->paid_at) && $p->paid_at->greaterThanOrEqualTo($now->copy()->startOfMonth()))->sum('amount');
        $yearRevenue = (clone $payments)->where('payment_status', 'confirmed')->filter(fn($p) => optional($p->paid_at) && $p->paid_at->greaterThanOrEqualTo($now->copy()->startOfYear()))->sum('amount');

        $confirmedPayments = $payments->where('payment_status', 'confirmed');
        $orderCountsByStatus = $orders->countBy('status');
        $quoteApproved = $orders->filter(fn($o) => !empty($o->approved_quote_id))->count();
        $delayedOrders = $orders->filter(fn($o) => $o->due_date && $o->due_date->isPast() && !in_array($o->status, ['completed','cancelled']))->count();

        return [
            'shop' => $shop,
            'settings' => OwnerSetting::where('shop_id', $shop->id)->first(),
            'overview' => [
                'stats' => [
                    'total_orders' => $orders->count(),
                    'pending_orders' => $orders->whereIn('status', $pendingStatuses)->count(),
                    'active_orders' => $orders->whereIn('status', $activeStatuses)->count(),
                    'completed_orders' => $orders->where('status', 'completed')->count(),
                    'total_earnings' => round((float) $confirmedPayments->sum('amount'), 2),
                    'low_stock_materials' => $materials->filter(fn($m) => $m->reorder_level !== null && $m->stock_quantity <= $m->reorder_level)->count(),
                    'today_revenue' => round((float) $todayRevenue, 2),
                    'week_revenue' => round((float) $weekRevenue, 2),
                    'month_revenue' => round((float) $monthRevenue, 2),
                    'year_revenue' => round((float) $yearRevenue, 2),
                ],
                'recent_orders' => $recentOrders,
                'alerts' => $alerts,
                'pending_proof_requests' => $proofRequests->whereIn('status', ['draft', 'submitted', 'pricing_ready'])->values()->take(8),
                'low_stock_items' => $materials->filter(fn($m) => $m->reorder_level !== null && $m->stock_quantity <= $m->reorder_level)->values()->take(8),
            ],
            'orders' => $orders,
            'design_proofing' => $proofRequests,
            'pricing' => $services,
            'payments' => $payments,
            'earnings' => [
                'summary' => [
                    'today' => round((float) $todayRevenue, 2),
                    'week' => round((float) $weekRevenue, 2),
                    'month' => round((float) $monthRevenue, 2),
                    'year' => round((float) $yearRevenue, 2),
                    'average_order_value' => round($orders->count() ? ((float) $orders->sum('total_amount') / max($orders->count(), 1)) : 0, 2),
                    'pending_receivables' => round((float) $orders->whereNotIn('payment_status', ['paid','confirmed'])->sum('total_amount'), 2),
                ],
                'daily_revenue' => $this->series($confirmedPayments, 'paid_at', 'Y-m-d'),
                'weekly_revenue' => $this->series($confirmedPayments, 'paid_at', 'o-\\WW'),
                'monthly_revenue' => $this->series($confirmedPayments, 'paid_at', 'Y-m'),
            ],
            'production_tracking' => $orders->map(fn($o) => [
                'order_id' => $o->id,
                'order_number' => $o->order_number,
                'client' => $o->client?->name,
                'assigned_staff' => $o->assignments()->with('assignee:id,name')->latest('id')->first()?->assignee?->name,
                'stage' => $o->current_stage,
                'priority' => $o->due_date && $o->due_date->isPast() ? 'high' : 'normal',
                'due_date' => $o->due_date,
                'status' => $o->status,
                'last_update' => $o->updated_at,
            ])->values(),
            'quality_control' => $qualityChecks,
            'projects' => $projects,
            'supplier_management' => $suppliers,
            'raw_materials' => $materials,
            'supply_chain' => $supplyOrders,
            'staff' => $staff,
            'operations' => $staff->map(function ($member) {
                $assignments = $member->assignedOrderAssignments;
                return [
                    'staff_id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role,
                    'active_tasks' => $assignments->whereIn('status', ['pending','accepted','in_progress'])->count(),
                    'completed_tasks' => $assignments->where('status', 'completed')->count(),
                    'delayed_tasks' => $assignments->filter(fn($a) => $a->assigned_at && $a->assigned_at->lt(now()->subDays(3)) && $a->status !== 'completed')->count(),
                    'revision_jobs' => $assignments->where('assignment_type', 'revision')->count(),
                ];
            })->values(),
            'workforce_scheduling' => $schedules,
            'delivery_pickup' => $orders->map(fn($o) => [
                'order_id' => $o->id,
                'order_number' => $o->order_number,
                'customer' => $o->client?->name,
                'courier' => optional($o->fulfillment)->courier_name,
                'delivery_type' => $o->fulfillment_type,
                'tracking_reference' => optional($o->fulfillment)->tracking_number,
                'pickup_date' => optional($o->fulfillment)->pickup_scheduled_at,
                'status' => optional($o->fulfillment)->status ?? 'pending',
                'shipping_fee' => optional($o->fulfillment)->shipping_fee,
            ])->values(),
            'dispute_resolution' => $disputes,
            'messages' => $threads,
            'marketplace' => [
                'shop_projects' => $projects,
                'client_design_requests' => DesignCustomization::query()->doesntHave('order')->latest('id')->limit(50)->get(),
            ],
            'analytics' => [
                'cards' => [
                    'total_orders' => $orders->count(),
                    'total_revenue' => round((float) $confirmedPayments->sum('amount'), 2),
                    'pending_orders' => $orders->whereIn('status', $pendingStatuses)->count(),
                    'completed_orders' => $orders->where('status', 'completed')->count(),
                    'monthly_growth' => 0,
                    'quote_requests' => $proofRequests->count(),
                    'approved_quotes' => $quoteApproved,
                    'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
                    'delayed_orders' => $delayedOrders,
                ],
                'order_status_breakdown' => $orderCountsByStatus,
                'daily_revenue' => $this->series($confirmedPayments, 'paid_at', 'Y-m-d'),
                'weekly_revenue' => $this->series($confirmedPayments, 'paid_at', 'o-\\WW'),
                'monthly_revenue' => $this->series($confirmedPayments, 'paid_at', 'Y-m'),
            ],
        ];
    }

    protected function series($items, string $dateField, string $format): array
    {
        return $items->groupBy(function ($item) use ($dateField, $format) {
            return optional($item->{$dateField})->format($format) ?? 'N/A';
        })->map(fn($group, $label) => ['label' => $label, 'value' => round((float) $group->sum('amount'), 2)])->values()->all();
    }
}
