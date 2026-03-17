<?php

namespace App\Services;

use App\Models\OwnerSetting;
use App\Models\PriceSuggestionRule;
use App\Models\ShopService;

class PricingSuggestionService
{
    public function estimate(array $payload, ?ShopService $shopService = null): array
    {
        $quantity = max((int) ($payload['quantity'] ?? 1), 1);
        $colors = max((int) ($payload['color_count'] ?? $payload['thread_colors'] ?? 1), 1);
        $complexity = $payload['complexity_level'] ?? 'standard';
        $width = (float) ($payload['width_mm'] ?? 80);
        $height = (float) ($payload['height_mm'] ?? 80);
        $rush = (bool) ($payload['is_rush'] ?? false);
        $fabricType = strtolower((string) ($payload['fabric_type'] ?? ''));
        $placementArea = strtolower((string) ($payload['placement_area'] ?? ''));
        $designType = strtolower((string) ($payload['design_type'] ?? 'logo'));

        $stitches = (int) ($payload['stitch_count_estimate'] ?? $payload['stitch_count'] ?? 0);
        if ($stitches < 1) {
            $area = max($width * $height, 900);
            $density = match ($complexity) {
                'simple' => 0.65,
                'complex' => 1.1,
                'premium' => 1.35,
                default => 0.85,
            };
            $stitches = (int) round(max(($area * $density) + ($colors * 250), 1200));
        }

        $ownerSettings = $shopService?->shop_id ? OwnerSetting::query()->where('shop_id', $shopService->shop_id)->first() : null;
        $pricingRules = $ownerSettings?->pricing_rules_json ?? [];
        $automationControls = $ownerSettings?->quote_automation_controls_json ?? [];

        $baseUnit = $shopService ? (float) $shopService->base_price : 120.0;
        $sizeMultiplier = max(($width * $height) / 6400, 0.75);
        foreach (($pricingRules['size_rules'] ?? []) as $sizeRule) {
            if (!empty($sizeRule['max_dimensions']) && preg_match('/(\d+)\s*[xX]\s*(\d+)/', (string) $sizeRule['max_dimensions'], $m)) {
                if ($width <= (float) $m[1] && $height <= (float) $m[2]) {
                    $sizeMultiplier = max($sizeMultiplier, (float) ($sizeRule['multiplier'] ?? 1));
                    break;
                }
            }
        }
        $stitchFactor = max($stitches / 5000, 0.8);
        $colorRuleSet = $pricingRules['color_rules'] ?? [];
        $colorRule = $colors <= 3 ? ($colorRuleSet['1_3'] ?? []) : ($colors <= 6 ? ($colorRuleSet['4_6'] ?? []) : ($colorRuleSet['7_plus'] ?? []));
        $colorFactor = 1 + max($colors - 1, 0) * 0.06;
        $colorExtra = ((float) ($colorRule['extra_cost_per_color'] ?? 0)) * max($colors - 1, 0);
        $premiumThreadSurcharge = !empty($payload['premium_thread']) ? (float) ($colorRule['premium_thread_surcharge'] ?? 0) : 0;
        $complexityRule = collect($pricingRules['complexity_rules'] ?? [])->firstWhere('level', $complexity);
        $complexityFactor = (float) (($complexityRule['stitch_multiplier'] ?? null) ?: match ($complexity) {
            'simple' => 0.9,
            'moderate' => 1.0,
            'complex' => 1.3,
            'highly_complex', 'premium' => 1.6,
            default => 1.0,
        });

        $fabricFactor = match (true) {
            str_contains($fabricType, 'fleece'), str_contains($fabricType, 'hoodie') => 1.10,
            str_contains($fabricType, 'cap') => 1.12,
            str_contains($fabricType, 'polyester') => 1.04,
            default => 1.00,
        };

        $placementFactor = match (true) {
            str_contains($placementArea, 'back') => 1.18,
            str_contains($placementArea, 'sleeve') => 1.08,
            str_contains($placementArea, 'cap') => 1.10,
            default => 1.00,
        };

        $quantityDiscount = $quantity >= 250 ? 0.74 : ($quantity >= 100 ? 0.82 : ($quantity >= 50 ? 0.88 : ($quantity >= 20 ? 0.94 : 1.0)));

        $basePrice = round($baseUnit * $sizeMultiplier * $stitchFactor * $colorFactor * $complexityFactor * $fabricFactor * $placementFactor, 2);
        $subtotalBeforeRules = round($basePrice * $quantity * $quantityDiscount, 2);
        $subtotal = $subtotalBeforeRules;
        $digitizingFee = $stitches > 12000 ? 420 : ($stitches > 9000 ? 350 : 200);
        if ($complexityRule) {
            $digitizingFee = round($digitizingFee * max((float) ($complexityRule['digitizing_multiplier'] ?? 1), 0), 2);
        }
        $rushConfig = $pricingRules['rush_rules'] ?? [];
        $rushMultiplier = $rush ? (float) (($rushConfig['24_hour_rush']['multiplier'] ?? null) ?: ($shopService?->rush_multiplier ?? 1.15)) : 0;
        $rushFee = $rush ? round($subtotal * max($rushMultiplier - 1, 0.01), 2) : 0;
        $materialFee = round($quantity * max($colors, 1) * 4.5, 2) + $colorExtra + $premiumThreadSurcharge;
        foreach (($pricingRules['material_surcharges'] ?? []) as $materialRule) {
            if (!empty($payload[$materialRule['key'] ?? ''])) {
                $materialFee += (float) ($materialRule['amount'] ?? 0);
            }
        }
        $adjustments = [];

        $rules = PriceSuggestionRule::query()->where('is_active', true)->orderByDesc('priority')->get();
        foreach ($rules as $rule) {
            $conditions = $rule->conditions_json ?? [];
            if (($conditions['minimum_quantity'] ?? null) && $quantity < (int) $conditions['minimum_quantity']) continue;
            if (($conditions['maximum_quantity'] ?? null) && $quantity > (int) $conditions['maximum_quantity']) continue;
            if (($conditions['complexity_level'] ?? null) && $conditions['complexity_level'] !== $complexity) continue;
            if (($conditions['design_type'] ?? null) && strtolower((string) $conditions['design_type']) !== $designType) continue;
            if (($conditions['fabric_type'] ?? null) && strtolower((string) $conditions['fabric_type']) !== $fabricType) continue;
            if (($conditions['placement_area'] ?? null) && strtolower((string) $conditions['placement_area']) !== $placementArea) continue;
            if (($conditions['color_count_min'] ?? null) && $colors < (int) $conditions['color_count_min']) continue;
            if (($conditions['color_count_max'] ?? null) && $colors > (int) $conditions['color_count_max']) continue;
            if (($conditions['stitch_count_min'] ?? null) && $stitches < (int) $conditions['stitch_count_min']) continue;
            if (($conditions['stitch_count_max'] ?? null) && $stitches > (int) $conditions['stitch_count_max']) continue;
            if (($conditions['requires_rush'] ?? false) && ! $rush) continue;

            $value = (float) $rule->amount_value;
            $delta = $rule->amount_type === 'percent'
                ? round($subtotal * ($value / 100), 2)
                : $value;
            $adjustments[] = [
                'rule' => $rule->rule_name,
                'rule_code' => $rule->rule_code,
                'delta' => $delta,
            ];
            $subtotal += $delta;
        }

        $laborFee = !empty($automationControls['auto_add_labor_estimate']) ? round(($ownerSettings?->default_labor_rate ?? 0) * max($quantity, 1), 2) : 0;
        $shippingFee = !empty($automationControls['auto_add_shipping_estimate']) && ($payload['fulfillment_type'] ?? null) === 'delivery' ? 150 : 0;
        $discountRules = $pricingRules['discount_rules'] ?? [];
        $discountAmount = 0;
        foreach (($discountRules['bulk_discounts'] ?? []) as $discountRule) {
            if ($quantity >= (int) ($discountRule['min_qty'] ?? PHP_INT_MAX)) {
                $discountAmount = max($discountAmount, round(($subtotal * ((float) ($discountRule['percent'] ?? 0) / 100)), 2));
            }
        }
        $total = round(max(($ownerSettings?->minimum_billable_amount ?? 0), $subtotal + $digitizingFee + $materialFee + ($automationControls['auto_add_rush_fee'] ?? true ? $rushFee : 0) + $laborFee + $shippingFee - $discountAmount), 2);
        $confidence = 72;
        $confidence += !empty($payload['artwork_path']) ? 8 : 0;
        $confidence += !empty($payload['width_mm']) && !empty($payload['height_mm']) ? 5 : 0;
        $confidence += !empty($payload['stitch_count_estimate']) ? 8 : 0;
        $confidence += $shopService ? 5 : 0;
        $confidence -= $rush ? 3 : 0;
        $confidence = max(45, min(98, $confidence));

        $pricingStrategy = $confidence >= 90
            ? 'high_confidence_auto_quote'
            : ($confidence >= 75 ? 'guided_quote_review' : 'manual_quote_review');

        return [
            'base_unit_price' => $basePrice,
            'subtotal_before_rules' => round($subtotalBeforeRules, 2),
            'subtotal' => round($subtotal, 2),
            'digitizing_fee' => $digitizingFee,
            'material_fee' => $materialFee,
            'rush_fee' => $rushFee,
            'labor_fee' => $laborFee,
            'shipping_fee' => $shippingFee,
            'discount_amount' => $discountAmount,
            'suggested_total' => $total,
            'stitch_count_estimate' => $stitches,
            'color_count' => $colors,
            'quantity_discount_factor' => $quantityDiscount,
            'fabric_factor' => $fabricFactor,
            'placement_factor' => $placementFactor,
            'complexity_factor' => $complexityFactor,
            'confidence_score' => $confidence,
            'pricing_strategy' => $pricingStrategy,
            'adjustments' => $adjustments,
        ];
    }
}
