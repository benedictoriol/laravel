<?php

namespace App\Services;

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

        $baseUnit = $shopService ? (float) $shopService->base_price : 120.0;
        $sizeMultiplier = max(($width * $height) / 6400, 0.75);
        $stitchFactor = max($stitches / 5000, 0.8);
        $colorFactor = 1 + max($colors - 1, 0) * 0.06;
        $complexityFactor = match ($complexity) {
            'simple' => 0.9,
            'complex' => 1.3,
            'premium' => 1.6,
            default => 1.0,
        };

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
        $rushFee = $rush ? round($subtotal * 0.15, 2) : 0;
        $materialFee = round($quantity * max($colors, 1) * 4.5, 2);
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

        $total = round($subtotal + $digitizingFee + $materialFee + $rushFee, 2);
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
