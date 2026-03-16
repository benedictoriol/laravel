<?php

namespace App\Services;

use App\Models\DssRecommendation;
use App\Models\DssShopMetric;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderException;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Models\Review;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsAutomationService
{
    public function refreshForOrder(Order $order, ?string $basis = null): array
    {
        $order->loadMissing(['shop', 'client']);

        $risk = $this->scanOrderRisk($order, $basis);
        $metric = $this->refreshShopMetrics($order->shop_id, $basis ?? 'order_refresh');

        if ($order->client_user_id) {
            $this->refreshClientRecommendations((int) $order->client_user_id, $basis ?? 'order_refresh');
        }

        return [
            'risk' => $risk,
            'metric' => $metric,
        ];
    }

    public function refreshShopMetrics(int $shopId, string $basis = 'manual_refresh'): DssShopMetric
    {
        $today = now()->toDateString();

        $orders = Order::query()->where('shop_id', $shopId);
        $totalOrders = (clone $orders)->count();
        $completedOrders = (clone $orders)->where('status', 'completed')->count();
        $cancelledOrders = (clone $orders)->where('status', 'cancelled')->count();
        $revenueTotal = (float) (clone $orders)->where('status', 'completed')->sum('total_amount');

        $avgTurnaround = (float) Order::query()
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at) / 24) as avg_days')
            ->value('avg_days');

        $reviewStats = Review::query()
            ->where('shop_id', $shopId)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as avg_rating')
            ->first();

        $activeStaffCount = (int) DB::table('shop_members')
            ->where('shop_id', $shopId)
            ->whereIn('member_role', ['hr', 'staff'])
            ->where('employment_status', 'active')
            ->count();

        $openJobPostsTaken = DB::getSchemaBuilder()->hasTable('job_openings')
            ? (int) DB::table('job_openings')->where('shop_id', $shopId)->where('status', 'open')->count()
            : 0;

        $shopAvgPrice = DB::table('shop_services')
            ->where('shop_id', $shopId)
            ->where('is_active', 1)
            ->avg('base_price');
        $globalAvgPrice = DB::table('shop_services')
            ->where('is_active', 1)
            ->avg('base_price');

        $priceCompetitiveness = null;
        if ($shopAvgPrice !== null && $globalAvgPrice !== null && (float) $globalAvgPrice > 0) {
            $ratio = ((float) $globalAvgPrice / max((float) $shopAvgPrice, 1));
            $priceCompetitiveness = round(max(0, min(100, $ratio * 100)), 2);
        }

        $completionRate = $totalOrders > 0 ? round($completedOrders / $totalOrders, 4) : 0;
        $ratingComponent = ((float) ($reviewStats->avg_rating ?? 0) / 5) * 100;
        $completionComponent = $completionRate * 100;
        $priceComponent = (float) ($priceCompetitiveness ?? 50);
        $riskScore = $this->computeShopDelayRisk($shopId);
        $recommendationScore = round(max(0, min(100,
            ($completionComponent * 0.40) +
            ($ratingComponent * 0.35) +
            ($priceComponent * 0.15) +
            ((100 - $riskScore) * 0.10)
        )), 2);

        return DssShopMetric::updateOrCreate(
            ['shop_id' => $shopId, 'metric_date' => $today],
            [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'avg_rating' => round((float) ($reviewStats->avg_rating ?? 0), 2),
                'review_count' => (int) ($reviewStats->review_count ?? 0),
                'completion_rate' => $completionRate,
                'avg_turnaround_days' => round((float) $avgTurnaround, 2),
                'active_staff_count' => $activeStaffCount,
                'open_job_posts_taken' => $openJobPostsTaken,
                'revenue_total' => round($revenueTotal, 2),
                'price_competitiveness_score' => $priceCompetitiveness,
                'recommendation_score' => $recommendationScore,
                'delay_risk_score' => $riskScore,
            ]
        );
    }

    public function refreshClientRecommendations(int $clientUserId, string $basis = 'manual_refresh')
    {
        $latestMetrics = DssShopMetric::query()
            ->with('shop')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')->from('dss_shop_metrics')->groupBy('shop_id');
            })
            ->orderByDesc('recommendation_score')
            ->limit(5)
            ->get();

        DssRecommendation::query()
            ->where('client_user_id', $clientUserId)
            ->where('generated_for_type', 'client')
            ->delete();

        $rank = 1;
        foreach ($latestMetrics as $metric) {
            DssRecommendation::create([
                'client_user_id' => $clientUserId,
                'shop_id' => $metric->shop_id,
                'generated_for_type' => 'client',
                'basis' => $basis,
                'score' => $metric->recommendation_score ?? 0,
                'rank_position' => $rank++,
                'context_json' => json_encode([
                    'completion_rate' => $metric->completion_rate,
                    'avg_rating' => $metric->avg_rating,
                    'delay_risk_score' => $metric->delay_risk_score,
                    'price_competitiveness_score' => $metric->price_competitiveness_score,
                ]),
                'generated_at' => now(),
            ]);
        }

        return $latestMetrics;
    }

    public function scanOrderRisk(Order $order, ?string $basis = null): array
    {
        $order->loadMissing(['shop']);
        if (in_array($order->status, ['completed', 'cancelled'], true)) {
            return [
                'order_id' => $order->id,
                'risk_score' => 0,
                'alerts' => [],
            ];
        }

        $alerts = [];
        $riskScore = 0;

        $activeStage = OrderStageHistory::query()
            ->where('order_id', $order->id)
            ->where('stage_status', 'active')
            ->latest('id')
            ->first();

        if ($activeStage && $activeStage->started_at) {
            $started = Carbon::parse($activeStage->started_at);
            $ageDays = $started->diffInHours(now()) / 24;
            $threshold = $this->stageThresholdDays($activeStage->stage_code);
            if ($ageDays > $threshold) {
                $alerts[] = 'Active stage '.$activeStage->stage_code.' is overdue.';
                $riskScore += min(35, ($ageDays - $threshold) * 12);
                $this->openRiskException($order, 'stage_delay', 'high', 'Stage '.$activeStage->stage_code.' is overdue.', $basis);
            }
        }

        $overdueAssignments = OrderAssignment::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->where('assigned_at', '<=', now()->subDays(2))
            ->count();
        if ($overdueAssignments > 0) {
            $alerts[] = $overdueAssignments.' active assignment(s) are overdue.';
            $riskScore += min(35, $overdueAssignments * 12);
            $this->openRiskException($order, 'assignment_delay', 'medium', $overdueAssignments.' assignment(s) are overdue.', $basis);
        }

        if ($order->due_date && Carbon::parse($order->due_date)->isPast()) {
            $alerts[] = 'Order due date has passed.';
            $riskScore += 25;
            $this->openRiskException($order, 'due_date_breach', 'critical', 'Order due date has passed.', $basis);
        }

        $openExceptions = OrderException::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['open', 'in_progress', 'escalated'])
            ->count();
        if ($openExceptions > 0) {
            $riskScore += min(20, $openExceptions * 5);
        }

        $riskScore = round(min(100, $riskScore), 2);

        return [
            'order_id' => $order->id,
            'risk_score' => $riskScore,
            'alerts' => $alerts,
        ];
    }

    protected function computeShopDelayRisk(int $shopId): float
    {
        $activeAssignments = OrderAssignment::query()
            ->whereHas('order', fn ($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();

        $overdueAssignments = OrderAssignment::query()
            ->whereHas('order', fn ($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', ['assigned', 'in_progress'])
            ->where('assigned_at', '<=', now()->subDays(2))
            ->count();

        $openExceptions = OrderException::query()
            ->whereHas('order', fn ($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', ['open', 'in_progress', 'escalated'])
            ->count();

        $risk = ($activeAssignments > 0 ? ($overdueAssignments / max($activeAssignments, 1)) * 70 : 0) + min(30, $openExceptions * 5);

        return round(min(100, $risk), 2);
    }

    protected function stageThresholdDays(string $stageCode): float
    {
        return match ($stageCode) {
            'digitizing', 'mockup', 'quality_check', 'packing' => 1,
            'client_approval', 'pickup_ready', 'shipping' => 2,
            'production' => 3,
            default => 2,
        };
    }

    protected function openRiskException(Order $order, string $type, string $severity, string $notes, ?string $basis = null): void
    {
        $exists = OrderException::query()
            ->where('order_id', $order->id)
            ->where('exception_type', $type)
            ->whereIn('status', ['open', 'in_progress', 'escalated'])
            ->exists();

        if ($exists) {
            return;
        }

        $exception = OrderException::create([
            'order_id' => $order->id,
            'exception_type' => $type,
            'severity' => $severity,
            'status' => $severity === 'critical' ? 'escalated' : 'open',
            'notes' => trim($notes.' '.($basis ? '[basis: '.$basis.']' : '')),
            'escalated_at' => $severity === 'critical' ? now() : null,
        ]);

        $recipientIds = DB::table('shop_members')
            ->where('shop_id', $order->shop_id)
            ->whereIn('member_role', ['owner', 'hr'])
            ->where('employment_status', 'active')
            ->pluck('user_id')
            ->all();

        foreach (array_unique($recipientIds) as $userId) {
            PlatformNotification::create([
                'user_id' => $userId,
                'type' => 'order_delay_risk',
                'title' => 'Order risk detected',
                'message' => 'Analytics flagged order '.$order->order_number.' for '.$type.'.',
                'reference_type' => 'exception',
                'reference_id' => $exception->id,
                'channel' => 'web',
            ]);
        }
    }
}
