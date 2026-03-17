<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignCustomization;
use App\Models\DesignCustomizationSnapshot;
use App\Models\DesignPost;
use App\Models\DesignWorkflowEvent;
use App\Models\OrderProgressLog;
use App\Models\PlatformNotification;
use App\Models\ShopService;
use App\Services\PricingSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignCustomizationController extends Controller
{
    public function __construct(private PricingSuggestionService $pricingSuggestionService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = DesignCustomization::with(['designPost', 'order', 'proofs', 'snapshots', 'workflowEvents', 'productionPackages', 'latestProductionPackage'])->latest('id');
        if ($user->isClient()) {
            $query->where('user_id', $user->id);
        } elseif (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('order', fn ($oq) => $oq->where('shop_id', $user->shop_id ?? 0))
                  ->orWhereHas('designPost', fn ($dq) => $dq->where('selected_shop_id', $user->shop_id ?? 0)->orWhereNull('selected_shop_id'));
            });
        }
        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request, false);
        $service = !empty($validated['service_id']) ? ShopService::find($validated['service_id']) : null;
        $pricing = $this->pricingSuggestionService->estimate($validated, $service);
        $phaseFour = $this->phaseFourComputedFields(array_merge($validated, [
            'color_count' => $pricing['color_count'] ?? null,
            'stitch_count_estimate' => $pricing['stitch_count_estimate'] ?? null,
            'pricing_breakdown_json' => $pricing,
        ]));

        $customization = DesignCustomization::create(array_merge([
            'design_post_id' => $validated['design_post_id'] ?? null,
            'order_id' => $validated['order_id'] ?? null,
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'garment_type' => $validated['garment_type'] ?? null,
            'placement_area' => $validated['placement_area'] ?? null,
            'fabric_type' => $validated['fabric_type'] ?? null,
            'width_mm' => $validated['width_mm'] ?? null,
            'height_mm' => $validated['height_mm'] ?? null,
            'color_count' => $pricing['color_count'],
            'stitch_count_estimate' => $pricing['stitch_count_estimate'],
            'complexity_level' => $validated['complexity_level'] ?? 'standard',
            'special_styles_json' => $validated['special_styles_json'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'artwork_path' => $validated['artwork_path'] ?? null,
            'preview_path' => $validated['preview_path'] ?? null,
            'status' => 'estimated',
            'workflow_status' => 'draft',
            'current_version_no' => 1,
            'estimated_base_price' => $pricing['base_unit_price'],
            'estimated_total_price' => $pricing['suggested_total'],
            'pricing_breakdown_json' => $pricing,
            'design_session_json' => $validated['design_session_json'] ?? null,
            'preview_meta_json' => $validated['preview_meta_json'] ?? null,
            'pricing_confidence_score' => $pricing['confidence_score'],
            'pricing_strategy' => $pricing['pricing_strategy'],
            'last_priced_at' => now(),
        ], $phaseFour));

        $this->captureSnapshot($customization, $request->user()->id, 'Initial customization saved');
        $this->writeProgressAndNotifications($customization, $request->user()->id, true);

        $this->recordWorkflowEvent($customization, $request->user()->id, 'draft_created', 'Created design draft.', 'Initial design draft captured for proofing and quotation workflow.');

        return response()->json($customization->load(['proofs', 'snapshots', 'workflowEvents', 'productionPackages', 'latestProductionPackage']), 201);
    }

    public function show(DesignCustomization $designCustomization): JsonResponse
    {
        return response()->json($designCustomization->load(['designPost', 'order', 'proofs', 'snapshots.actor', 'approvedProof', 'workflowEvents.actor', 'productionPackages.creator', 'latestProductionPackage']));
    }

    public function update(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $validated = $this->validatePayload($request, true);
        abort_if($designCustomization->status === 'approved' && array_key_exists('status', $validated) && $validated['status'] !== 'approved', 422, 'Approved customizations can no longer be moved backward.');

        $pricing = $this->pricingSuggestionService->estimate(array_merge($designCustomization->toArray(), $validated));
        $phaseFour = $this->phaseFourComputedFields(array_merge($designCustomization->toArray(), $validated, [
            'color_count' => $pricing['color_count'] ?? null,
            'stitch_count_estimate' => $pricing['stitch_count_estimate'] ?? null,
            'pricing_breakdown_json' => $pricing,
        ]));
        $designCustomization->update(array_merge($validated, [
            'color_count' => $pricing['color_count'],
            'stitch_count_estimate' => $pricing['stitch_count_estimate'],
            'estimated_base_price' => $pricing['base_unit_price'],
            'estimated_total_price' => $pricing['suggested_total'],
            'pricing_breakdown_json' => $pricing,
            'pricing_confidence_score' => $pricing['confidence_score'],
            'pricing_strategy' => $pricing['pricing_strategy'],
            'last_priced_at' => now(),
        ], $phaseFour));

        $fresh = $designCustomization->fresh();
        $this->captureSnapshot($fresh, $request->user()->id, 'Customization updated');
        $this->recordWorkflowEvent($fresh, $request->user()->id, 'autosaved', 'Designer changes saved.', 'Design metadata and editor state were updated.');

        return response()->json($fresh->load(['proofs', 'snapshots.actor', 'approvedProof', 'workflowEvents.actor', 'productionPackages.creator', 'latestProductionPackage']));
    }

    public function suggestPrice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['nullable', 'integer', 'exists:shop_services,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'stitch_count_estimate' => ['nullable', 'integer', 'min:1'],
            'color_count' => ['nullable', 'integer', 'min:1'],
            'complexity_level' => ['nullable', 'in:simple,standard,complex,premium'],
            'width_mm' => ['nullable', 'numeric', 'min:1'],
            'height_mm' => ['nullable', 'numeric', 'min:1'],
            'design_type' => ['nullable', 'string', 'max:50'],
            'placement_area' => ['nullable', 'string', 'max:100'],
            'fabric_type' => ['nullable', 'string', 'max:100'],
            'is_rush' => ['nullable', 'boolean'],
        ]);
        $service = !empty($validated['service_id']) ? ShopService::find($validated['service_id']) : null;
        return response()->json($this->pricingSuggestionService->estimate($validated, $service));
    }

    protected function validatePayload(Request $request, bool $partial): array
    {
        $base = [
            'design_post_id' => ['nullable', 'integer', 'exists:design_posts,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:180'],
            'garment_type' => ['nullable', 'string', 'max:100'],
            'placement_area' => ['nullable', 'string', 'max:100'],
            'fabric_type' => ['nullable', 'string', 'max:100'],
            'width_mm' => ['nullable', 'numeric', 'min:1'],
            'height_mm' => ['nullable', 'numeric', 'min:1'],
            'color_count' => ['nullable', 'integer', 'min:1'],
            'stitch_count_estimate' => ['nullable', 'integer', 'min:1'],
            'complexity_level' => ['nullable', 'in:simple,standard,complex,premium'],
            'special_styles_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'artwork_path' => ['nullable', 'string', 'max:255'],
            'preview_path' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'integer', 'exists:shop_services,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'design_type' => ['nullable', 'string', 'max:50'],
            'is_rush' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,estimated,proof_ready,approved,archived'],
            'design_session_json' => ['nullable', 'array'],
            'preview_meta_json' => ['nullable', 'array'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ];
        return $request->validate($base);
    }

    protected function captureSnapshot(DesignCustomization $customization, int $userId, string $summary): void
    {
        $version = ((int) $customization->snapshots()->max('version_no')) + 1;
        DesignCustomizationSnapshot::create([
            'design_customization_id' => $customization->id,
            'version_no' => $version,
            'captured_by' => $userId,
            'change_summary' => $summary,
            'snapshot_json' => $customization->only([
                'name','garment_type','placement_area','fabric_type','width_mm','height_mm','color_count','stitch_count_estimate','complexity_level','special_styles_json','notes','artwork_path','preview_path','status','design_session_json','preview_meta_json','production_status','color_mapping_json','risk_flags_json','suggested_quote_basis_json'
            ]),
            'pricing_snapshot_json' => $customization->pricing_breakdown_json,
        ]);

        if (array_key_exists('current_version_no', $customization->getAttributes())) {
            $customization->forceFill(['current_version_no' => $version])->save();
        }
    }

    protected function recordWorkflowEvent(DesignCustomization $customization, int $actorId, string $type, string $summary, ?string $details = null): void
    {
        if (! class_exists(DesignWorkflowEvent::class)) return;
        DesignWorkflowEvent::create([
            'design_customization_id' => $customization->id,
            'actor_user_id' => $actorId,
            'event_type' => $type,
            'summary' => $summary,
            'details' => $details,
        ]);
    }


    protected function phaseFourComputedFields(array $payload): array
    {
        $previewMeta = is_array($payload['preview_meta_json'] ?? null) ? $payload['preview_meta_json'] : [];
        $designSession = is_array($payload['design_session_json'] ?? null) ? $payload['design_session_json'] : [];
        $layers = collect($designSession['layers'] ?? [])->filter(fn ($layer) => is_array($layer))->values();
        $colors = $layers->flatMap(fn ($layer) => array_values(array_filter([data_get($layer, 'color'), data_get($layer, 'fill'), data_get($layer, 'stroke'), data_get($layer, 'meta.primaryColor')])))->map(fn ($value) => strtoupper((string) $value))->filter()->unique()->values();
        $threadMapping = $colors->map(function ($hex, $index) {
            return [
                'hex' => $hex,
                'thread_code' => 'THR-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'thread_name' => $this->threadNameFromHex($hex),
            ];
        })->values()->all();

        $width = (float) ($payload['width_mm'] ?? 0);
        $height = (float) ($payload['height_mm'] ?? 0);
        $stitches = (int) ($payload['stitch_count_estimate'] ?? data_get($previewMeta, 'stitch_estimate', 0));
        $complexity = strtolower(str_replace(' ', '_', (string) ($payload['complexity_level'] ?? data_get($previewMeta, 'complexity', 'medium'))));
        $colorCount = max((int) ($payload['color_count'] ?? 0), count($threadMapping), count((array) data_get($previewMeta, 'palette', [])));
        $placement = (string) ($payload['placement_area'] ?? 'left_chest');
        $garment = (string) ($payload['garment_type'] ?? 'polo');
        $isRush = (bool) ($payload['is_rush'] ?? false);
        $revisionCount = (int) data_get($payload, 'revision_count', 0);
        $area = max(1, $width * $height);
        $complexityFactor = match ($complexity) {
            'very_high', 'premium' => 1.8,
            'high', 'complex' => 1.45,
            'medium', 'standard' => 1.2,
            default => 1.0,
        };
        $placementSurcharge = in_array($placement, ['cap_front', 'cap_side', 'sleeve'], true) ? 85 : (in_array($placement, ['full_front', 'back'], true) ? 120 : 0);
        $garmentFactor = in_array($garment, ['cap', 'hoodie', 'jacket'], true) ? 1.18 : 1.0;
        $digitizingFee = round(max(180, 140 + ($stitches * 0.018 * $complexityFactor) + ($colorCount * 16)), 2);
        $revisionOverhead = round($revisionCount * 45, 2);
        $rushFee = $isRush ? round(max(120, $digitizingFee * 0.18), 2) : 0;
        $estimatedUnit = round((85 + ($stitches * 0.014) + ($colorCount * 18)) * $garmentFactor + $placementSurcharge, 2);
        $suggestedTotal = round($estimatedUnit + $digitizingFee + $revisionOverhead + $rushFee, 2);
        $riskFlags = [];
        foreach ((array) ($previewMeta['warnings'] ?? []) as $warning) $riskFlags[] = ['level' => 'warning', 'message' => (string) $warning];
        if ($colorCount >= 8) $riskFlags[] = ['level' => 'high', 'message' => 'High thread color count may require production simplification.'];
        if ($stitches >= 14000) $riskFlags[] = ['level' => 'high', 'message' => 'High stitch count may increase run time and heat buildup.'];
        if ($area >= 40000) $riskFlags[] = ['level' => 'medium', 'message' => 'Large embroidery area should be checked against placement stability.'];
        $riskFlags = collect($riskFlags)->unique(fn ($item) => $item['message'])->values()->all();

        return [
            'color_count' => $colorCount,
            'color_mapping_json' => $threadMapping,
            'risk_flags_json' => $riskFlags,
            'suggested_quote_basis_json' => [
                'estimated_digitizing_fee' => $digitizingFee,
                'complexity_factor' => $complexityFactor,
                'stitch_effort_factor' => round($stitches / 1000, 2),
                'placement_surcharge' => $placementSurcharge,
                'garment_factor' => $garmentFactor,
                'rush_fee' => $rushFee,
                'revision_overhead' => $revisionOverhead,
                'estimated_unit_price' => $estimatedUnit,
                'suggested_total' => $suggestedTotal,
                'color_count' => $colorCount,
            ],
            'production_meta_json' => array_merge(is_array($payload['production_meta_json'] ?? null) ? $payload['production_meta_json'] : [], [
                'production_color_summary' => array_map(fn ($item) => $item['thread_name'].' ('.$item['thread_code'].')', $threadMapping),
                'risk_flag_count' => count($riskFlags),
            ]),
        ];
    }

    protected function threadNameFromHex(string $hex): string
    {
        $hex = strtoupper($hex);
        if (str_contains($hex, '000000')) return 'Jet Black';
        if (str_contains($hex, 'FFFFFF') || str_contains($hex, 'FAFAF9')) return 'Bright White';
        if (preg_match('/^#?(FF|F5|F2)/', $hex)) return 'Warm White';
        if (preg_match('/^#?(E|D|C).*/', ltrim($hex, '#'))) return 'Stone Neutral';
        if (str_contains($hex, '0F') || str_contains($hex, '17')) return 'Deep Navy';
        return 'Custom Thread';
    }

    protected function writeProgressAndNotifications(DesignCustomization $customization, int $actorId, bool $created = false): void
    {
        if ($customization->order_id) {
            OrderProgressLog::create([
                'order_id' => $customization->order_id,
                'status' => $created ? 'design_customization_created' : 'design_customization_updated',
                'title' => $created ? 'Design customization saved' : 'Design customization updated',
                'description' => 'Customization “'.$customization->name.'” was processed with '.$customization->pricing_strategy.'.',
                'actor_user_id' => $actorId,
            ]);
        }

        if ($customization->design_post_id) {
            $post = DesignPost::find($customization->design_post_id);
            if ($post) {
                PlatformNotification::create([
                    'user_id' => $post->client_user_id,
                    'type' => 'design_customization_estimated',
                    'title' => 'Design estimate prepared',
                    'message' => 'A design customization estimate was prepared for “'.$post->title.'”.',
                    'reference_type' => 'design_customization',
                    'reference_id' => $customization->id,
                    'channel' => 'web',
                ]);
            }
        }
    }
}
