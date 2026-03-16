<?php

namespace App\Services;

use App\Models\ClientPaymentMethod;
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

class ClientWorkspaceService
{
    public function build(User $user): array
    {
        $orders = Order::query()
            ->with(['shop:id,shop_name', 'service:id,service_name', 'payments', 'fulfillment'])
            ->where('client_user_id', $user->id)
            ->latest('id')
            ->get();

        $designCustomizations = DesignCustomization::query()
            ->with(['designPost.selectedShop:id,shop_name', 'proofs', 'approvedProof'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $paymentMethods = ClientPaymentMethod::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();

        $projects = ShopProject::query()
            ->with('shop:id,shop_name,verification_status')
            ->where('is_active', true)
            ->latest('id')
            ->limit(50)
            ->get();

        $designRequests = DesignPost::query()
            ->with(['client:id,name', 'selectedShop:id,shop_name'])
            ->latest('id')
            ->limit(50)
            ->get();

        $previousShopIds = $orders->pluck('shop_id')->filter()->unique()->values();
        $messagingShops = Shop::query()
            ->whereIn('id', $previousShopIds)
            ->orderBy('shop_name')
            ->get(['id', 'shop_name', 'email', 'phone']);

        $threads = MessageThread::query()
            ->with(['messages.sender:id,name'])
            ->whereIn('shop_id', $previousShopIds)
            ->where(function ($query) use ($user) {
                $query->whereJsonContains('participant_user_ids_json', $user->id)
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('client_user_id', $user->id));
            })
            ->latest('last_message_at')
            ->get();

        $supportTickets = SupportTicket::query()
            ->with(['shop:id,shop_name', 'order:id,order_number'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $notifications = PlatformNotification::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(200)
            ->get();

        $approvedShops = Shop::query()
            ->where('verification_status', 'approved')
            ->where('is_active', true)
            ->orderBy('shop_name')
            ->get(['id', 'shop_name', 'email', 'phone', 'address_line']);

        $hiringOpenings = ShopHiringOpening::query()
            ->with('shop:id,shop_name')
            ->where('status', 'open')
            ->latest('id')
            ->limit(25)
            ->get();

        return [
            'overview' => [
                'stats' => [
                    'pending_quotes' => $designCustomizations->filter(fn ($item) => in_array($item->status, ['draft', 'estimated', 'proof_ready']))->count(),
                    'unpaid_partial_orders' => $orders->filter(fn ($order) => in_array($order->payment_status, ['unpaid', 'partial']))->count(),
                    'design_approvals_needed' => $designCustomizations->flatMap->proofs->where('status', 'pending_client')->count(),
                    'delivery_tracking' => $orders->filter(fn ($order) => in_array(optional($order->fulfillment)->status, ['ready', 'scheduled', 'shipped', 'out_for_delivery']))->count(),
                ],
                'projects' => $projects->take(8)->values(),
                'hiring_openings' => $hiringOpenings,
            ],
            'track_orders' => [
                'orders' => $orders,
                'counts' => [
                    'all' => $orders->count(),
                    'to_pay' => $this->filterOrders($orders, 'to_pay')->count(),
                    'to_process' => $this->filterOrders($orders, 'to_process')->count(),
                    'to_ship' => $this->filterOrders($orders, 'to_ship')->count(),
                    'to_receive' => $this->filterOrders($orders, 'to_receive')->count(),
                    'to_review' => $this->filterOrders($orders, 'to_review')->count(),
                    'returns' => $this->filterOrders($orders, 'returns')->count(),
                    'cancellation' => $this->filterOrders($orders, 'cancellation')->count(),
                ],
            ],
            'payment_methods' => $paymentMethods,
            'design_studio' => [
                'saved_designs' => $designCustomizations->take(30)->values(),
                'thread_palettes' => [
                    '#111827', '#ffffff', '#dc2626', '#ea580c', '#f59e0b', '#16a34a', '#0ea5e9', '#2563eb', '#7c3aed', '#ec4899'
                ],
                'shops' => $approvedShops,
            ],
            'design_proofing' => [
                'requests' => $designCustomizations,
                'shops' => $approvedShops,
                'services' => $approvedShops->pluck('shop_name', 'id'),
            ],
            'marketplace' => [
                'projects' => $projects,
                'design_requests' => $designRequests,
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
                    'pending_approvals' => $notifications->filter(fn ($item) => in_array($item->type, ['design_proof_ready', 'quote_ready', 'payment_action_required']))->count(),
                    'unread_notifications' => $notifications->where('is_read', false)->count(),
                ],
            ],
            'shops' => $approvedShops,
        ];
    }

    protected function filterOrders(Collection $orders, string $tab): Collection
    {
        return $orders->filter(function ($order) use ($tab) {
            $status = (string) $order->status;
            $paymentStatus = (string) $order->payment_status;
            $fulfillmentStatus = (string) optional($order->fulfillment)->status;

            return match ($tab) {
                'to_pay' => in_array($paymentStatus, ['unpaid', 'partial']),
                'to_process' => in_array($status, ['pending', 'quoted', 'approved', 'in_production']),
                'to_ship' => in_array($fulfillmentStatus, ['pending', 'ready', 'scheduled']) && in_array($status, ['ready_for_pickup', 'shipped']),
                'to_receive' => in_array($fulfillmentStatus, ['shipped', 'out_for_delivery']),
                'to_review' => $status === 'completed',
                'returns' => in_array($status, ['return_requested', 'returned']),
                'cancellation' => $status === 'cancelled',
                default => true,
            };
        })->values();
    }
}
