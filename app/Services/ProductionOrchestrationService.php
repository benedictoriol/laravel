<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\DssRecommendation;
use App\Models\DssShopMetric;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderException;
use App\Models\OrderProgressLog;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionOrchestrationService
{
    public function __construct(protected AutomationTraceService $trace)
    {
    }

    public function orchestrateAfterPayment(Order $order, ?User $actor = null): array
    {
        $order->loadMissing(['shop', 'client', 'customizations.proofs', 'assignments', 'fulfillment']);
        if (! $order->shop_id) {
            return [];
        }

        return DB::transaction(function () use ($order, $actor) {
            $recommendations = $this->recommendWorkforce($order->shop_id, $order);
            $materialPlan = $this->reserveMaterials($order, $actor);
            $createdAssignments = [];

            foreach ($recommendations as $recommendation) {
                $existing = OrderAssignment::query()
                    ->where('order_id', $order->id)
                    ->where('assignment_type', $recommendation['assignment_type'])
                    ->whereIn('status', ['assigned', 'in_progress', 'done'])
                    ->first();

                if ($existing || empty($recommendation['staff_id'])) {
                    continue;
                }

                $assignment = OrderAssignment::create([
                    'order_id' => $order->id,
                    'assigned_to' => $recommendation['staff_id'],
                    'assigned_by' => $actor?->id ?? $order->shop?->owner_user_id ?? $recommendation['staff_id'],
                    'assignment_role' => $recommendation['assignment_role'],
                    'assignment_type' => $recommendation['assignment_type'],
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'notes' => $recommendation['reason'],
                ]);

                $createdAssignments[] = $assignment;

                $this->trace->notify(
                    $recommendation['staff_id'],
                    'order_assignment_created',
                    'New automated assignment',
                    sprintf('You were auto-assigned %s for order %s.', str_replace('_', ' ', $recommendation['assignment_type']), $order->order_number),
                    'order',
                    $order->id,
                    ['category' => 'production', 'priority' => 'medium', 'action_label' => 'View order']
                );
            }

            $dueDate = $order->due_date ?: now()->addDays($this->estimateTurnaroundDays($order));
            $order->update([
                'status' => 'in_production',
                'current_stage' => 'digitizing',
                'due_date' => $dueDate,
                'internal_notes' => trim(($order->internal_notes ? $order->internal_notes."\n" : '').'Automation: production orchestration generated staffing, material reservation, and ETA.'),
            ]);

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'orchestrated',
                'title' => 'Production orchestration created',
                'description' => 'System generated staffing recommendations, reserved materials, and projected completion timing.',
                'actor_user_id' => $actor?->id,
            ]);

            $this->trace->log($actor?->id, $order->shop_id, 'order', $order->id, 'production_orchestrated', [
                'recommendations' => $recommendations,
                'materials' => $materialPlan,
                'due_date' => optional($dueDate)->toDateString(),
            ], [], [
                'reason' => 'payment_confirmed',
                'automation' => 'production_orchestration',
            ]);

            $shop = $order->shop;
            if ($shop) {
                $this->trace->notifyShopLeads($shop, 'production_orchestrated', 'Production plan ready', 'The system prepared a production plan for order '.$order->order_number.'.', 'order', $order->id, [
                    'category' => 'production',
                    'priority' => 'medium',
                    'action_label' => 'Review plan',
                ]);
            }

            $this->trace->notify($order->client_user_id, 'order_production_scheduled', 'Order scheduled for production', 'Your order '.$order->order_number.' has been scheduled automatically with an estimated completion plan.', 'order', $order->id, [
                'category' => 'production',
                'priority' => 'medium',
                'action_label' => 'Track order',
            ]);

            return [
                'recommendations' => $recommendations,
                'materials' => $materialPlan,
                'assignments_created' => collect($createdAssignments)->map->only(['id', 'assigned_to', 'assignment_type', 'status'])->values()->all(),
                'due_date' => optional($dueDate)->toDateString(),
            ];
        });
    }

    public function recommendWorkforce(int $shopId, ?Order $order = null): array
    {
        $staff = User::query()
            ->where('shop_id', $shopId)
            ->whereIn('role', ['hr', 'staff'])
            ->where('is_active', true)
            ->get();

        $assignmentTypes = ['digitizing', 'embroidery', 'quality_check', 'packing'];
        $result = [];

        foreach ($assignmentTypes as $type) {
            $candidate = $staff
                ->sortBy([
                    fn (User $user) => $user->assignedOrderAssignments()->whereIn('status', ['assigned', 'in_progress'])->count(),
                    fn (User $user) => $user->role === 'staff' ? 0 : 1,
                ])->first();

            $load = $candidate?->assignedOrderAssignments()->whereIn('status', ['assigned', 'in_progress'])->count() ?? null;
            $result[] = [
                'assignment_type' => $type,
                'assignment_role' => ($candidate?->role === 'hr') ? 'hr' : 'staff',
                'staff_id' => $candidate?->id,
                'staff_name' => $candidate?->name,
                'reason' => $candidate
                    ? sprintf('System assigned %s because %s has the lowest active load (%d task%s).', $type, $candidate->name, $load, $load === 1 ? '' : 's')
                    : 'No active staff available for automatic assignment.',
                'predicted_load' => $load,
                'order_id' => $order?->id,
            ];
        }

        return $result;
    }

    public function reserveMaterials(Order $order, ?User $actor = null): array
    {
        $customization = $order->customizations()->latest('id')->first();
        $colors = (int) ($customization->color_count ?? 1);
        $quantity = max(1, (int) ($customization->quantity ?? $order->items()->sum('quantity') ?: 1));
        $stitches = max(1000, (int) ($customization->stitch_count_estimate ?? 1500));

        $threadUsage = max(1, (int) ceil(($stitches * $quantity) / 5000));
        $backingUsage = max(1, (int) ceil($quantity / 5));
        $stabilizerUsage = max(1, (int) ceil($quantity / 10));

        $requirements = [
            ['category' => 'thread', 'quantity' => $threadUsage, 'label' => $colors.' color thread set'],
            ['category' => 'backing', 'quantity' => $backingUsage, 'label' => 'backing'],
            ['category' => 'stabilizer', 'quantity' => $stabilizerUsage, 'label' => 'stabilizer'],
        ];

        $materialPlan = [];
        foreach ($requirements as $requirement) {
            $material = RawMaterial::query()
                ->where('shop_id', $order->shop_id)
                ->where('category', 'like', '%'.$requirement['category'].'%')
                ->orderByDesc('stock_quantity')
                ->first();

            if (! $material) {
                $this->createShortageAlert($order, $requirement['label'], $requirement['quantity']);
                $materialPlan[] = ['category' => $requirement['category'], 'material' => null, 'required' => $requirement['quantity'], 'reserved' => 0, 'status' => 'missing'];
                continue;
            }

            $before = (float) $material->stock_quantity;
            $reserved = min($before, (float) $requirement['quantity']);
            $after = max(0, $before - $reserved);
            $material->update(['stock_quantity' => $after]);

            if ($after <= (float) ($material->reorder_level ?? 0)) {
                $this->createShortageAlert($order, $material->material_name, (float) $requirement['quantity'], $after);
            }

            $this->trace->log($actor?->id, $order->shop_id, 'raw_material', $material->id, 'auto_reserved_for_order', [
                'order_id' => $order->id,
                'reserved_quantity' => $reserved,
                'remaining_quantity' => $after,
            ], [
                'stock_quantity' => $before,
            ], [
                'automation' => 'material_intelligence',
            ]);

            $materialPlan[] = [
                'category' => $requirement['category'],
                'material' => $material->material_name,
                'required' => $requirement['quantity'],
                'reserved' => $reserved,
                'remaining' => $after,
                'status' => $reserved >= $requirement['quantity'] ? 'reserved' : 'partial',
            ];
        }

        return $materialPlan;
    }

    public function scanOrderHealth(Order $order): array
    {
        $order->loadMissing(['assignments', 'payments', 'fulfillment', 'progressLogs']);
        $hoursSinceUpdate = $order->updated_at ? $order->updated_at->diffInHours(now()) : 0;
        $delayRisk = 'low';
        $signals = [];

        if ($hoursSinceUpdate >= 24) {
            $delayRisk = 'medium';
            $signals[] = 'stage idle more than 24h';
        }
        if ($hoursSinceUpdate >= 48 || ($order->due_date && Carbon::parse($order->due_date)->isPast() && ! in_array($order->status, ['completed', 'cancelled'], true))) {
            $delayRisk = 'high';
            $signals[] = 'likely delayed within 24h';
        }
        if ($order->payment_status !== 'paid' && $order->approved_quote_id && $order->payment_due_date && Carbon::parse($order->payment_due_date)->isPast()) {
            $delayRisk = 'high';
            $signals[] = 'payment overdue';
        }

        if ($delayRisk !== 'low') {
            $this->trace->alertOnce($order->shop_id, $order->id, 'delay_prediction', $delayRisk, 'Delay risk detected', 'Order '.$order->order_number.' shows delay risk: '.implode(', ', $signals).'.', 'order', $order->id, [
                'signals' => $signals,
                'current_stage' => $order->current_stage,
            ]);
        }

        return [
            'order_id' => $order->id,
            'risk' => $delayRisk,
            'signals' => $signals,
            'hours_since_update' => $hoursSinceUpdate,
        ];
    }

    public function buildClientTrust(Order $order): array
    {
        $order->loadMissing(['assignments.assignee:id,name', 'progressLogs', 'fulfillment']);
        $lastProgress = $order->progressLogs()->latest('id')->first();
        $activeAssignment = $order->assignments()->with('assignee:id,name')->whereIn('status', ['assigned', 'in_progress'])->latest('id')->first();
        $health = $this->scanOrderHealth($order);

        $confidence = match ($health['risk']) {
            'high' => 'Low',
            'medium' => 'Medium',
            default => 'High',
        };

        return [
            'handled_by' => $activeAssignment?->assignee?->name ?? 'Production Team',
            'current_stage' => $order->current_stage,
            'estimated_completion' => optional($order->due_date)->toDateString(),
            'confidence' => $confidence,
            'last_activity_at' => optional($lastProgress?->created_at ?? $order->updated_at)->toDateTimeString(),
            'recommended_action' => $order->payment_status !== 'paid' ? 'Complete payment requirements' : 'Wait for the current production stage to finish',
        ];
    }

    public function recommendMarketplaceShops(User $client, ?DesignCustomization $design = null): Collection
    {
        $metricDate = now()->toDateString();
        $metrics = DssShopMetric::query()->with('shop')->whereDate('metric_date', $metricDate)->get();
        if ($metrics->isEmpty()) {
            $metrics = DssShopMetric::query()->with('shop')->latest('metric_date')->get();
        }

        $recommended = $metrics->sortByDesc(function ($metric) {
            return (($metric->completion_rate ?? 0) * 40)
                + (($metric->avg_rating ?? 0) * 10)
                + (($metric->recommendation_score ?? 0) * 0.4)
                - (($metric->delay_risk_score ?? 0) * 0.3);
        })->take(5)->values();

        return $recommended->map(function ($metric, $index) use ($client, $design) {
            DssRecommendation::updateOrCreate(
                [
                    'client_user_id' => $client->id,
                    'shop_id' => $metric->shop_id,
                    'generated_for_type' => 'client',
                ],
                [
                    'basis' => 'completion_rate, rating, recommendation score, delay risk',
                    'score' => (($metric->completion_rate ?? 0) * 40) + (($metric->avg_rating ?? 0) * 10) + (($metric->recommendation_score ?? 0) * 0.4) - (($metric->delay_risk_score ?? 0) * 0.3),
                    'rank_position' => $index + 1,
                    'context_json' => [
                        'recommendation_context' => 'marketplace_match',
                        'design_type' => $design?->design_type,
                        'quantity' => $design?->quantity,
                    ],
                    'generated_at' => now(),
                ]
            );

            return [
                'shop_id' => $metric->shop_id,
                'shop_name' => $metric->shop?->shop_name,
                'completion_rate' => $metric->completion_rate,
                'avg_rating' => $metric->avg_rating,
                'delay_risk_score' => $metric->delay_risk_score,
                'recommendation_score' => $metric->recommendation_score,
                'reason' => 'Recommended for stronger completion rate, rating, and lower delay risk.',
            ];
        });
    }

    public function routeException(Order $order, string $type, string $notes, string $severity = 'medium', ?int $handlerId = null): OrderException
    {
        $exception = OrderException::create([
            'order_id' => $order->id,
            'exception_type' => $type,
            'severity' => $severity,
            'status' => in_array($severity, ['high', 'critical'], true) ? 'escalated' : 'open',
            'notes' => $notes,
            'assigned_handler_id' => $handlerId,
            'escalated_at' => in_array($severity, ['high', 'critical'], true) ? now() : null,
        ]);

        $this->trace->alertOnce($order->shop_id, $order->id, 'exception_'.$type, $severity, 'Order exception: '.str_replace('_', ' ', $type), $notes, 'order_exception', $exception->id, [
            'order_number' => $order->order_number,
        ]);

        $this->trace->notify($order->client_user_id, 'order_exception_created', 'Order update requires attention', 'Order '.$order->order_number.' triggered an exception workflow: '.str_replace('_', ' ', $type).'.', 'order_exception', $exception->id, [
            'category' => 'exceptions',
            'priority' => $severity,
            'action_label' => 'View update',
        ]);

        return $exception;
    }

    protected function estimateTurnaroundDays(Order $order): int
    {
        $customization = $order->customizations()->latest('id')->first();
        $complexity = strtolower((string) ($customization->complexity_level ?? 'medium'));
        return match ($complexity) {
            'high' => 5,
            'medium' => 4,
            default => 3,
        };
    }

    protected function createShortageAlert(Order $order, string $materialName, float $required, ?float $remaining = null): void
    {
        $message = sprintf('%s is insufficient for order %s. Required: %s. %s', $materialName, $order->order_number, rtrim(rtrim((string) $required, '0'), '.'), $remaining !== null ? 'Remaining after reservation: '.rtrim(rtrim((string) $remaining, '0'), '.').'.' : '');
        $this->trace->alertOnce($order->shop_id, $order->id, 'material_shortage', 'high', 'Material shortage predicted', $message, 'order', $order->id, [
            'material' => $materialName,
            'required' => $required,
            'remaining' => $remaining,
        ]);
    }
}
