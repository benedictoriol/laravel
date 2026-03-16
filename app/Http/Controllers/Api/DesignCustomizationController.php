<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignCustomization;
use App\Models\DesignCustomizationSnapshot;
use App\Models\DesignPost;
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
        $query = DesignCustomization::with(['designPost', 'order', 'proofs', 'snapshots'])->latest('id');
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

        $customization = DesignCustomization::create([
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
            'estimated_base_price' => $pricing['base_unit_price'],
            'estimated_total_price' => $pricing['suggested_total'],
            'pricing_breakdown_json' => $pricing,
            'design_session_json' => $validated['design_session_json'] ?? null,
            'preview_meta_json' => $validated['preview_meta_json'] ?? null,
            'pricing_confidence_score' => $pricing['confidence_score'],
            'pricing_strategy' => $pricing['pricing_strategy'],
            'last_priced_at' => now(),
        ]);

        $this->captureSnapshot($customization, $request->user()->id, 'Initial customization saved');
        $this->writeProgressAndNotifications($customization, $request->user()->id, true);

        return response()->json($customization->load(['proofs', 'snapshots']), 201);
    }

    public function show(DesignCustomization $designCustomization): JsonResponse
    {
        return response()->json($designCustomization->load(['designPost', 'order', 'proofs', 'snapshots', 'approvedProof']));
    }

    public function update(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $validated = $this->validatePayload($request, true);
        abort_if($designCustomization->status === 'approved' && array_key_exists('status', $validated) && $validated['status'] !== 'approved', 422, 'Approved customizations can no longer be moved backward.');

        $pricing = $this->pricingSuggestionService->estimate(array_merge($designCustomization->toArray(), $validated));
        $designCustomization->update(array_merge($validated, [
            'color_count' => $pricing['color_count'],
            'stitch_count_estimate' => $pricing['stitch_count_estimate'],
            'estimated_base_price' => $pricing['base_unit_price'],
            'estimated_total_price' => $pricing['suggested_total'],
            'pricing_breakdown_json' => $pricing,
            'pricing_confidence_score' => $pricing['confidence_score'],
            'pricing_strategy' => $pricing['pricing_strategy'],
            'last_priced_at' => now(),
        ]));

        $this->captureSnapshot($designCustomization->fresh(), $request->user()->id, 'Customization updated');

        return response()->json($designCustomization->fresh(['proofs', 'snapshots', 'approvedProof']));
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
                'name','garment_type','placement_area','fabric_type','width_mm','height_mm','color_count','stitch_count_estimate','complexity_level','special_styles_json','notes','artwork_path','preview_path','status','design_session_json','preview_meta_json'
            ]),
            'pricing_snapshot_json' => $customization->pricing_breakdown_json,
        ]);
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
