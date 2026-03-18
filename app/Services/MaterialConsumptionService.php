<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\MaterialConsumption;
use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MaterialConsumptionService
{
    public function __construct(protected AutomationTraceService $trace)
    {
    }

    public function ensureEstimatedForOrder(Order $order, ?User $actor = null): array
    {
        $order->loadMissing(['customizations.latestProductionPackage']);
        $design = $order->customizations->sortByDesc('id')->first();
        $package = $design?->latestProductionPackage;

        if (! $design) {
            return [];
        }

        return DB::transaction(function () use ($order, $design, $package, $actor) {
            $records = [];

            foreach ($this->buildEstimates($order, $design) as $estimate) {
                $material = $this->resolveMaterial($order, $estimate);
                $record = MaterialConsumption::query()->firstOrNew([
                    'shop_id' => $order->shop_id,
                    'order_id' => $order->id,
                    'design_customization_id' => $design->id,
                    'production_package_id' => $package?->id,
                    'material_key' => $estimate['material_key'],
                ]);

                $previousDeducted = (float) ($record->deducted_quantity ?? 0);
                $record->fill([
                    'raw_material_id' => $material?->id,
                    'material_category' => $estimate['material_category'],
                    'material_name_snapshot' => $material?->material_name ?? $estimate['label'],
                    'color_label' => $estimate['color_label'] ?? null,
                    'unit' => $material?->unit ?? $estimate['unit'],
                    'estimate_quantity' => $estimate['estimate_quantity'],
                    'status' => $material ? MaterialConsumption::STATUS_SUGGESTED : MaterialConsumption::STATUS_UNAVAILABLE,
                    'source_meta_json' => $estimate['meta'],
                ]);
                $record->save();

                if ($material) {
                    $targetDeduction = min((float) $estimate['estimate_quantity'], max(0, (float) $material->stock_quantity + $previousDeducted));
                    $delta = round($targetDeduction - $previousDeducted, 4);
                    if (abs($delta) > 0.00009) {
                        $this->applyStockDelta($order, $record, $material, $delta, $actor, 'estimated_consumption_sync');
                    }
                    if ($targetDeduction < (float) $estimate['estimate_quantity']) {
                        $record->update(['status' => MaterialConsumption::STATUS_PARTIAL]);
                    }
                }

                $records[] = $record->fresh()->load([
                    'order:id,order_number,current_stage,status',
                    'design:id,name',
                    'productionPackage:id,package_no,status',
                    'rawMaterial:id,material_name,category,color,unit,stock_quantity',
                    'confirmer:id,name',
                ]);
            }

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'material_consumption_estimated',
                'title' => 'Material consumption estimated',
                'description' => 'System estimated and reserved embroidery material usage for production.',
                'actor_user_id' => $actor?->id,
            ]);

            return collect($records)->map(fn (MaterialConsumption $row) => $this->transform($row))->values()->all();
        });
    }

    public function updateActual(Shop $shop, MaterialConsumption $consumption, User $actor, array $payload): MaterialConsumption
    {
        abort_unless((int) $consumption->shop_id === (int) $shop->id, 403, 'Consumption record does not belong to this shop.');

        return DB::transaction(function () use ($shop, $consumption, $actor, $payload) {
            $actual = round((float) ($payload['actual_quantity'] ?? $consumption->actual_quantity ?? $consumption->estimate_quantity ?? 0), 4);
            $material = $consumption->rawMaterial;
            $beforeDeducted = (float) ($consumption->deducted_quantity ?? 0);
            $targetDeducted = $beforeDeducted;

            if ($material) {
                $availablePlusReserved = max(0, (float) $material->stock_quantity + $beforeDeducted);
                $targetDeducted = min($actual, $availablePlusReserved);
                $delta = round($targetDeducted - $beforeDeducted, 4);
                if (abs($delta) > 0.00009) {
                    $this->applyStockDelta($consumption->order, $consumption, $material, $delta, $actor, 'actual_consumption_confirmation');
                }
            }

            $status = $actual === (float) ($consumption->estimate_quantity ?? 0)
                ? MaterialConsumption::STATUS_CONFIRMED
                : MaterialConsumption::STATUS_ADJUSTED;
            if ($material && $targetDeducted < $actual) {
                $status = MaterialConsumption::STATUS_PARTIAL;
            }
            if (! $material) {
                $status = MaterialConsumption::STATUS_UNAVAILABLE;
            }

            $meta = $consumption->source_meta_json ?? [];
            $meta['owner_note'] = $payload['owner_note'] ?? Arr::get($meta, 'owner_note');
            $meta['adjustment_reason'] = $payload['adjustment_reason'] ?? Arr::get($meta, 'adjustment_reason');
            $meta['last_confirmed_by'] = $actor->id;

            $consumption->update([
                'actual_quantity' => $actual,
                'status' => $status,
                'source_meta_json' => $meta,
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
            ]);

            OrderProgressLog::create([
                'order_id' => $consumption->order_id,
                'status' => 'material_consumption_confirmed',
                'title' => 'Material deduction confirmed',
                'description' => 'Owner confirmed actual material usage for '.$consumption->material_name_snapshot.'.',
                'actor_user_id' => $actor->id,
            ]);

            $this->trace->log($actor->id, $shop->id, 'material_consumption', $consumption->id, 'confirm_material_consumption', [
                'actual_quantity' => $actual,
                'deducted_quantity' => (float) ($consumption->fresh()->deducted_quantity ?? 0),
                'status' => $status,
            ]);

            return $consumption->fresh()->load([
                'order:id,order_number,current_stage,status',
                'design:id,name',
                'productionPackage:id,package_no,status',
                'rawMaterial:id,material_name,category,color,unit,stock_quantity',
                'confirmer:id,name',
            ]);
        });
    }

    public function listForShop(Shop $shop): Collection
    {
        return MaterialConsumption::query()
            ->with([
                'order:id,order_number,current_stage,status',
                'design:id,name',
                'productionPackage:id,package_no,status',
                'rawMaterial:id,material_name,category,color,unit,stock_quantity',
                'confirmer:id,name',
            ])
            ->where('shop_id', $shop->id)
            ->latest('id')
            ->get()
            ->map(fn (MaterialConsumption $row) => $this->transform($row));
    }

    protected function buildEstimates(Order $order, DesignCustomization $design): array
    {
        $quantity = max(1, (int) ($design->quantity ?? $order->items()->sum('quantity') ?: 1));
        $stitches = max(1000, (int) ($design->stitch_count_estimate ?? 1500));
        $colors = max(1, (int) ($design->color_count ?? 1));
        $width = max(1, (float) ($design->width_mm ?? 90));
        $height = max(1, (float) ($design->height_mm ?? 90));
        $areaSqCm = max(4, round(($width * $height) / 100, 2));
        $isPatch = str_contains(strtolower((string) ($design->garment_type ?? '')), 'patch') || str_contains(strtolower((string) ($order->order_type ?? '')), 'patch');

        $rows = [];
        $threadTotal = round(($stitches * $quantity) / 4500, 4);
        foreach ($this->normalizeColorWeights(collect($design->color_mapping_json ?: [])->values(), $colors) as $index => $color) {
            $rows[] = [
                'material_category' => 'thread',
                'material_key' => 'thread:'.($color['label'] ?: ('color_'.($index + 1))),
                'label' => 'Thread '.($color['label'] ?: ('Color '.($index + 1))),
                'color_label' => $color['label'],
                'estimate_quantity' => round($threadTotal * $color['weight'], 4),
                'unit' => 'cones',
                'meta' => [
                    'estimation_basis' => 'stitch_count',
                    'stitch_count' => $stitches,
                    'quantity' => $quantity,
                    'color_weight' => $color['weight'],
                ],
            ];
        }

        $rows[] = [
            'material_category' => 'stabilizer',
            'material_key' => 'stabilizer:main',
            'label' => 'Stabilizer',
            'estimate_quantity' => round(max(1, ($areaSqCm * $quantity) / 140), 4),
            'unit' => 'sheets',
            'meta' => ['estimation_basis' => 'design_area', 'design_area_sq_cm' => $areaSqCm, 'quantity' => $quantity],
        ];
        $rows[] = [
            'material_category' => 'backing',
            'material_key' => 'backing:main',
            'label' => 'Backing material',
            'estimate_quantity' => round(max(1, ($areaSqCm * $quantity) / 180), 4),
            'unit' => 'sheets',
            'meta' => ['estimation_basis' => 'design_area', 'design_area_sq_cm' => $areaSqCm, 'quantity' => $quantity],
        ];

        if ($isPatch) {
            $rows[] = [
                'material_category' => 'patch_base',
                'material_key' => 'patch_base:main',
                'label' => 'Patch base',
                'estimate_quantity' => (float) $quantity,
                'unit' => 'pcs',
                'meta' => ['estimation_basis' => 'patch_quantity', 'quantity' => $quantity],
            ];
        }

        return $rows;
    }

    protected function normalizeColorWeights(Collection $mappings, int $fallbackColors): array
    {
        if ($mappings->isEmpty()) {
            return collect(range(1, $fallbackColors))
                ->map(fn ($index) => ['label' => 'Color '.$index, 'weight' => round(1 / $fallbackColors, 4)])
                ->all();
        }

        $normalized = $mappings->map(function ($item, $index) {
            $ratio = (float) (Arr::get($item, 'ratio') ?? Arr::get($item, 'weight') ?? Arr::get($item, 'percent', 0));
            if ($ratio > 1) {
                $ratio = $ratio / 100;
            }
            return [
                'label' => Arr::get($item, 'color') ?? Arr::get($item, 'name') ?? Arr::get($item, 'thread') ?? ('Color '.($index + 1)),
                'weight' => $ratio > 0 ? $ratio : null,
            ];
        })->values();

        $known = $normalized->filter(fn ($row) => $row['weight'] !== null);
        if ($known->isEmpty()) {
            return $normalized->map(fn ($row) => ['label' => $row['label'], 'weight' => round(1 / max(1, $normalized->count()), 4)])->all();
        }

        $total = max(0.0001, $known->sum('weight'));
        return $normalized->map(fn ($row) => [
            'label' => $row['label'],
            'weight' => round(($row['weight'] ?? (1 / max(1, $normalized->count()))) / $total, 4),
        ])->all();
    }

    protected function resolveMaterial(Order $order, array $estimate): ?RawMaterial
    {
        $category = $estimate['material_category'] === 'patch_base' ? 'fabric' : $estimate['material_category'];
        $query = RawMaterial::query()->where('shop_id', $order->shop_id)->where(function ($inner) use ($category) {
            $inner->where('category', $category)->orWhere('category', 'like', '%'.$category.'%');
        });

        if (($estimate['material_category'] ?? null) === 'thread' && ! empty($estimate['color_label'])) {
            $query->orderByRaw('CASE WHEN color = ? THEN 0 ELSE 1 END', [$estimate['color_label']]);
        }

        return $query->orderByDesc('stock_quantity')->first();
    }

    protected function applyStockDelta(Order $order, MaterialConsumption $record, RawMaterial $material, float $delta, ?User $actor, string $reason): void
    {
        $before = (float) $material->stock_quantity;
        $after = $delta >= 0 ? max(0, $before - $delta) : ($before + abs($delta));
        $material->update(['stock_quantity' => $after]);
        $record->update(['deducted_quantity' => round(max(0, (float) $record->deducted_quantity + $delta), 4)]);

        $this->trace->log($actor?->id, $order->shop_id, 'raw_material', $material->id, 'material_consumption_stock_adjusted', [
            'order_id' => $order->id,
            'consumption_id' => $record->id,
            'delta' => $delta,
            'reason' => $reason,
            'remaining_quantity' => $after,
        ], [
            'stock_quantity' => $before,
        ]);
    }

    protected function transform(MaterialConsumption $row): array
    {
        return [
            'id' => $row->id,
            'order_id' => $row->order_id,
            'order' => $row->order,
            'design' => $row->design,
            'production_package' => $row->productionPackage,
            'raw_material' => $row->rawMaterial,
            'material_category' => $row->material_category,
            'material_key' => $row->material_key,
            'material_name_snapshot' => $row->material_name_snapshot,
            'color_label' => $row->color_label,
            'unit' => $row->unit,
            'estimate_quantity' => (float) $row->estimate_quantity,
            'deducted_quantity' => (float) ($row->deducted_quantity ?? 0),
            'actual_quantity' => $row->actual_quantity !== null ? (float) $row->actual_quantity : null,
            'status' => $row->status,
            'source_meta' => $row->source_meta_json ?? [],
            'confirmed_by' => $row->confirmer,
            'confirmed_at' => $row->confirmed_at,
        ];
    }
}
