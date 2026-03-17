<?php

namespace App\Services;

use App\Models\ClientPaymentMethod;
use App\Models\ClientProfile;
use App\Models\DesignCustomization;
use App\Models\DesignPost;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\Shop;
use App\Models\ShopHiringOpening;
use App\Models\ShopProject;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ClientWorkspaceService
{
    public function __construct(
        protected ProductionOrchestrationService $production,
        protected AutomationTraceService $trace,
    ) {}

    public function build(User $user): array
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $user->id]);
        $this->syncProfileDefaults($profile, $user);
        $profile->load('addresses');

        $orders = Order::query()
            ->with([
                'shop:id,shop_name',
                'service:id,service_name',
                'payments',
                'fulfillment',
                'assignments.assignee:id,name,role',
                'progressLogs',
                'quotes',
                'revisions',
            ])
            ->where('client_user_id', $user->id)
            ->latest('id')
            ->get();

        $designCustomizations = collect();
        try {
            $designQuery = DesignCustomization::query()->where('user_id', $user->id)->latest('id');
            if (Schema::hasTable('design_customizations')) {
                $designCustomizations = $designQuery->get()->map(function (DesignCustomization $design) {
                    $design->setAttribute('proof_history', collect());
                    $design->setAttribute('version_history', collect());
                    $design->setAttribute('activity_trail', collect());
                    $design->setAttribute('quote_history', collect());
                    $design->setAttribute('production_package_history', collect());
                    $design->setAttribute('risk_flag_count', count($design->risk_flags_json ?? []));
                    return $design;
                });
            }
        } catch (Throwable $e) {
            $designCustomizations = collect();
        }

        $paymentMethods = ClientPaymentMethod::query()->where('user_id', $user->id)->orderByDesc('is_default')->latest('id')->get();
        $projects = ShopProject::query()->with('shop:id,shop_name,verification_status')->where('is_active', true)->latest('id')->limit(50)->get();
        $myDesignRequests = DesignPost::query()->with(['client:id,name', 'selectedShop:id,shop_name', 'applications.shop', 'applications.owner'])->where('client_user_id', $user->id)->latest('id')->get();
        $publicDesignRequests = DesignPost::query()->with(['client:id,name', 'selectedShop:id,shop_name', 'applications.shop', 'applications.owner'])->where('visibility', 'public')->latest('id')->limit(50)->get();

        $previousShopIds = $orders->pluck('shop_id')->filter()->unique()->values();
        $projectShopIds = $projects->pluck('shop_id')->filter()->unique();
        $proposalShopIds = $myDesignRequests->pluck('selected_shop_id')->filter()->merge($myDesignRequests->flatMap(fn ($post) => $post->applications->pluck('shop_id')))->filter()->unique();
        $messagingShops = Shop::query()->whereIn('id', $previousShopIds->merge($projectShopIds)->merge($proposalShopIds)->unique()->values())->orderBy('shop_name')->get(['id', 'shop_name', 'email', 'phone']);
        $threadQuery = MessageThread::query()->with(['messages.sender:id,name']);
        $hasParticipantJson = Schema::hasColumn('message_threads', 'participant_user_ids_json');

        $threadQuery->where(function ($query) use ($previousShopIds, $user, $hasParticipantJson) {
            $query->whereIn('shop_id', $previousShopIds);

            if ($hasParticipantJson) {
                $query->orWhereJsonContains('participant_user_ids_json', $user->id);
            }
        });

        $threadQuery->where(function ($query) use ($user, $hasParticipantJson) {
            if ($hasParticipantJson) {
                $query->whereJsonContains('participant_user_ids_json', $user->id)
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('client_user_id', $user->id));

                return;
            }

            $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('client_user_id', $user->id));
        });

        try {
            $threads = $threadQuery->latest('last_message_at')->get();
        } catch (Throwable $e) {
            $threads = collect();
        }

        $supportTickets = SupportTicket::query()
            ->with(['shop:id,shop_name', 'order:id,order_number,current_stage,status', 'assignee:id,name'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get()
            ->map(function ($ticket) {
                $ticket->setAttribute('linked_reference', $ticket->order?->order_number ?: ('TICKET-'.$ticket->id));
                $ticket->setAttribute('linked_stage', $ticket->order?->current_stage);
                $ticket->setAttribute('next_action', in_array($ticket->status, ['resolved', 'closed'], true) ? 'No action needed' : 'Monitor support update');
                return $ticket;
            });

        $deduped = $this->trace->normalizeNotifications([$user->id]);
        $notifications = PlatformNotification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(function ($notification) {
                $notification->setAttribute('category', $notification->category ?: $this->inferCategory($notification->type, $notification->reference_type));
                $notification->setAttribute('priority', $notification->priority ?: $this->inferPriority($notification->type, $notification->created_at));
                $notification->setAttribute('action_label', $notification->action_label ?: $this->inferActionLabel($notification->reference_type));
                return $notification;
            });

        $approvedShops = Shop::query()->with('metrics')->where('verification_status', 'approved')->where('is_active', true)->orderBy('shop_name')->get();
        $hiringOpenings = ShopHiringOpening::query()->with('shop:id,shop_name')->whereHas('shop', fn ($query) => $query->where('verification_status', 'approved'))->latest('id')->limit(20)->get();
        $recommendedShops = $this->production->recommendMarketplaceShops($user, $designCustomizations->first());

        $orders = $orders->map(function (Order $order) {
            $trust = $this->production->buildClientTrust($order);
            $timeline = $order->progressLogs->sortByDesc('id')->take(8)->values()->map(fn ($log) => [
                'id' => $log->id,
                'status' => $log->status,
                'title' => $log->title,
                'description' => $log->description,
                'created_at' => optional($log->created_at)->toDateTimeString(),
            ]);
            $selfService = [
                'can_cancel' => in_array($order->status, ['pending', 'quoted', 'approved'], true),
                'can_review' => $order->status === 'completed',
                'can_request_return' => in_array(optional($order->fulfillment)->status, ['delivered', 'picked_up'], true),
                'can_message_shop' => (bool) $order->shop_id,
                'can_pay' => in_array($order->payment_status, ['unpaid', 'partial'], true),
            ];

            $order->setAttribute('trust', $trust);
            $order->setAttribute('timeline', $timeline);
            $order->setAttribute('self_service', $selfService);
            return $order;
        });

        return [
            'overview' => [
                'projects' => $projects,
                'hiring_openings' => $hiringOpenings,
                'stats' => [
                    'pending_quotes' => $designCustomizations->filter(fn ($item) => in_array($item->status, ['draft', 'estimated', 'proof_ready', 'submitted']))->count(),
                    'unpaid_partial_orders' => $orders->filter(fn ($order) => in_array($order->payment_status, ['unpaid', 'partial'], true))->count(),
                    'design_approvals_needed' => $designCustomizations->flatMap->proofs->where('status', 'pending_client')->count(),
                    'delivery_tracking' => $orders->filter(fn ($order) => in_array(optional($order->fulfillment)->status, ['ready', 'scheduled', 'shipped', 'out_for_delivery'], true))->count(),
                ],
                'active_journey' => $orders->first()?->trust,
                'recommended_shops' => $recommendedShops,
            ],
            'track_orders' => [
                'orders' => $orders,
                'tabs' => $this->buildOrderTabs($orders),
            ],
            'payment_methods' => $paymentMethods,
            'design_studio' => [
                'drafts' => $designCustomizations->whereNull('order_id')->values(),
                'latest_design' => $designCustomizations->first(),
            ],
            'design_proofing' => [
                'requests' => $designCustomizations,
                'shops' => $approvedShops,
            ],
            'marketplace' => [
                'projects' => $projects,
                'design_requests' => $publicDesignRequests,
                'my_design_requests' => $myDesignRequests,
                'recommended_shops' => $recommendedShops,
            ],
            'messages' => [
                'shops' => $messagingShops,
                'threads' => $threads,
            ],
            'support' => $supportTickets,
            'notifications' => [
                'items' => $notifications,
                'summary' => [
                    'urgent_alerts' => $notifications->whereIn('priority', ['high', 'critical'])->count(),
                    'pending_approvals' => $notifications->filter(fn ($item) => in_array($item->type, ['design_proof_ready', 'quote_ready', 'payment_action_required'], true))->count(),
                    'unread_notifications' => $notifications->where('is_read', false)->count(),
                    'by_category' => $notifications->countBy('category'),
                    'deduplicated' => $deduped,
                ],
            ],
            'shops' => $approvedShops,
            'client_profile' => $profile,
        ];
    }

    protected function syncProfileDefaults(ClientProfile $profile, User $user): void
    {
        $updates = [];

        if (Schema::hasColumn('client_profiles', 'email') && empty($profile->email) && ! empty($user->email)) {
            $updates['email'] = $user->email;
        }

        if (Schema::hasColumn('client_profiles', 'registration_date') && empty($profile->registration_date) && $user->created_at) {
            $updates['registration_date'] = $user->created_at->toDateString();
        }

        if (! empty($updates)) {
            $profile->forceFill($updates)->save();
        }
    }

    protected function buildOrderTabs(Collection $orders): array
    {
        return [
            'all' => $orders->count(),
            'to_pay' => $orders->filter(fn (Order $order) => in_array($order->payment_status, ['unpaid', 'partial'], true))->count(),
            'to_process' => $orders->filter(fn (Order $order) => in_array($order->status, ['pending', 'quoted', 'approved', 'in_production', 'on_hold'], true))->count(),
            'to_ship' => $orders->filter(fn (Order $order) => in_array(optional($order->fulfillment)->status, ['pending', 'ready', 'scheduled'], true) && in_array($order->status, ['ready_for_pickup', 'shipped', 'in_production'], true))->count(),
            'to_receive' => $orders->filter(fn (Order $order) => in_array(optional($order->fulfillment)->status, ['shipped', 'out_for_delivery'], true))->count(),
            'to_review' => $orders->where('status', 'completed')->count(),
            'returns' => $orders->filter(fn (Order $order) => in_array($order->status, ['return_requested', 'returned'], true))->count(),
            'cancellation' => $orders->where('status', 'cancelled')->count(),
        ];
    }

    protected function inferCategory(?string $type, ?string $referenceType): string
    {
        $haystack = strtolower((string) $type.' '.(string) $referenceType);
        return match (true) {
            str_contains($haystack, 'quote') => 'quotes',
            str_contains($haystack, 'payment') => 'payments',
            str_contains($haystack, 'delivery') || str_contains($haystack, 'shipment') => 'delivery',
            str_contains($haystack, 'support') => 'support',
            str_contains($haystack, 'inventory') || str_contains($haystack, 'material') => 'inventory',
            str_contains($haystack, 'exception') || str_contains($haystack, 'dispute') => 'exceptions',
            str_contains($haystack, 'proof') || str_contains($haystack, 'production') => 'production',
            default => 'orders',
        };
    }

    protected function inferPriority(?string $type, $createdAt): string
    {
        $haystack = strtolower((string) $type);
        if (str_contains($haystack, 'critical')) return 'critical';
        if (str_contains($haystack, 'rejected') || str_contains($haystack, 'overdue') || str_contains($haystack, 'delay')) return 'high';
        if (str_contains($haystack, 'payment') || str_contains($haystack, 'quote') || str_contains($haystack, 'proof')) return 'medium';
        if ($createdAt && now()->diffInHours($createdAt) <= 24) return 'medium';
        return 'low';
    }

    protected function inferActionLabel(?string $referenceType): string
    {
        return match ($referenceType) {
            'payment' => 'Pay now',
            'support_ticket' => 'Open ticket',
            'order', 'order_quote', 'design_proof' => 'View',
            default => 'Open',
        };
    }
}
