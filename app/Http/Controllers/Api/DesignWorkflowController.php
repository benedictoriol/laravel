<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignCustomization;
use App\Models\DesignCustomizationSnapshot;
use App\Models\DesignProductionPackage;
use App\Models\DesignProof;
use App\Models\DesignWorkflowEvent;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignWorkflowController extends Controller
{
    public function versions(DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeView($designCustomization, request()->user());

        return response()->json([
            'current_version_no' => (int) ($designCustomization->current_version_no ?: ($designCustomization->snapshots()->max('version_no') ?: 1)),
            'approved_version_no' => $designCustomization->approved_version_no,
            'versions' => $designCustomization->snapshots()->with('actor:id,name')->latest('version_no')->get(),
            'events' => $designCustomization->workflowEvents()->with('actor:id,name')->latest('id')->limit(50)->get(),
            'production_packages' => $designCustomization->productionPackages()->with('creator:id,name')->latest('id')->get(),
        ]);
    }

    public function createVersion(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        $validated = $request->validate([
            'change_summary' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $snapshot = $this->captureSnapshot($designCustomization->fresh(), $request->user()->id, $validated['change_summary'], $validated['notes'] ?? null);
        $this->recordEvent($designCustomization, $request->user()->id, 'version_created', $validated['change_summary'], $validated['notes'] ?? null, [
            'version_no' => $snapshot->version_no,
        ]);

        return response()->json($snapshot->load('actor:id,name'), 201);
    }

    public function restoreVersion(Request $request, DesignCustomization $designCustomization, DesignCustomizationSnapshot $snapshot): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        abort_unless($snapshot->design_customization_id === $designCustomization->id, 404);
        abort_if($this->isLockedWithoutOverride($designCustomization, $request), 422, 'Locked designs require an override reason before editing.');

        $payload = $snapshot->snapshot_json ?? [];
        $updates = collect($payload)->only([
            'name','garment_type','placement_area','fabric_type','width_mm','height_mm','color_count','stitch_count_estimate','complexity_level','special_styles_json','notes','artwork_path','preview_path','status','design_session_json','preview_meta_json'
        ])->toArray();
        $updates['current_version_no'] = $snapshot->version_no;
        $updates['workflow_status'] = $designCustomization->workflow_status ?: 'draft';
        $designCustomization->forceFill(array_merge($updates, $this->phaseFourComputedFields(array_merge($designCustomization->toArray(), $updates))))->save();

        $this->recordEvent($designCustomization, $request->user()->id, 'version_restored', 'Restored design version #'.$snapshot->version_no.'.', $snapshot->change_summary, [
            'version_no' => $snapshot->version_no,
        ]);

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function submitForQuotation(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        $phaseFour = $this->phaseFourComputedFields($designCustomization->toArray());
        $designCustomization->forceFill(array_merge($phaseFour, [
            'status' => 'estimated',
            'workflow_status' => 'submitted_for_review',
            'submitted_at' => now(),
        ]))->save();
        $snapshot = $this->captureSnapshot($designCustomization->fresh(), $request->user()->id, 'Submitted for quotation', $request->input('notes'));
        $this->recordEvent($designCustomization, $request->user()->id, 'submitted_for_quotation', 'Submitted design for quotation.', $request->input('notes'), [
            'version_no' => $snapshot->version_no,
            'quote_basis' => $designCustomization->fresh()->suggested_quote_basis_json,
        ]);
        $this->notifyShopSide($designCustomization, 'design_quote_requested', 'Design submitted for quotation', 'A client design is ready for pricing review.');

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function submitForProofing(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        $phaseFour = $this->phaseFourComputedFields($designCustomization->toArray());
        $designCustomization->forceFill(array_merge($phaseFour, [
            'status' => 'estimated',
            'workflow_status' => 'submitted_for_review',
            'submitted_at' => now(),
        ]))->save();
        $snapshot = $this->captureSnapshot($designCustomization->fresh(), $request->user()->id, 'Submitted for proofing', $request->input('notes'));
        $this->recordEvent($designCustomization, $request->user()->id, 'submitted_for_proofing', 'Submitted design for proofing.', $request->input('notes'), [
            'version_no' => $snapshot->version_no,
            'risk_flags' => $designCustomization->fresh()->risk_flags_json,
        ]);
        $this->notifyShopSide($designCustomization, 'design_proof_requested', 'Design submitted for proofing', 'A client design is ready for owner-side proof preparation.');

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function requestRevision(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $designCustomization->forceFill([
            'status' => 'estimated',
            'workflow_status' => 'revision_requested',
            'last_revision_requested_at' => now(),
        ])->save();
        $snapshot = $this->captureSnapshot($designCustomization->fresh(), $request->user()->id, 'Revision requested', $validated['reason']);
        $this->recordEvent($designCustomization, $request->user()->id, 'revision_requested', 'Revision requested for the design.', $validated['reason'], [
            'version_no' => $snapshot->version_no,
        ]);
        $this->notifyShopSide($designCustomization, 'design_revision_requested', 'Client requested a revision', $validated['reason']);

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function ownerCreateProof(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isAdmin() || $request->user()->isHr() || $request->user()->isStaff(), 403);
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $designCustomization->forceFill($this->phaseFourComputedFields($designCustomization->toArray()))->save();
        $versionNo = (int) ($designCustomization->current_version_no ?: ($designCustomization->snapshots()->max('version_no') ?: 1));
        $proof = DesignProof::create([
            'design_customization_id' => $designCustomization->id,
            'proof_no' => ((int) $designCustomization->proofs()->max('proof_no')) + 1,
            'version_no' => $versionNo,
            'generated_by' => $request->user()->id,
            'preview_file_path' => $designCustomization->preview_path ?: data_get($designCustomization->design_session_json, 'layers.0.src', ''),
            'annotated_notes' => $validated['notes'] ?? null,
            'pricing_snapshot_json' => array_merge($designCustomization->pricing_breakdown_json ?? [], [
                'quote_basis' => $designCustomization->suggested_quote_basis_json,
            ]),
            'proof_summary_json' => $this->buildProofSummary($designCustomization->fresh()),
            'status' => 'pending_client',
            'expires_at' => now()->addDays(3),
        ]);

        $designCustomization->forceFill([
            'status' => 'proof_ready',
            'workflow_status' => 'proof_ready',
        ])->save();
        $this->recordEvent($designCustomization, $request->user()->id, 'proof_generated', 'Generated proof #'.$proof->proof_no.'.', $validated['notes'] ?? null, [
            'proof_id' => $proof->id,
            'version_no' => $versionNo,
            'quote_basis' => $designCustomization->suggested_quote_basis_json,
        ]);

        PlatformNotification::create([
            'user_id' => $designCustomization->user_id,
            'type' => 'design_proof_ready',
            'category' => 'production',
            'priority' => 'medium',
            'title' => 'A new proof is ready',
            'message' => 'Review proof #'.$proof->proof_no.' for '.$designCustomization->name.'.',
            'action_label' => 'Review proof',
            'reference_type' => 'design_proof',
            'reference_id' => $proof->id,
            'channel' => 'web',
        ]);

        return response()->json($proof->fresh());
    }

    public function approve(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        $this->authorizeEdit($designCustomization, $request->user());
        $validated = $request->validate([
            'lock' => ['nullable', 'boolean'],
        ]);

        $currentVersion = (int) ($designCustomization->current_version_no ?: ($designCustomization->snapshots()->max('version_no') ?: 1));
        $designCustomization->forceFill(array_merge($this->phaseFourComputedFields($designCustomization->toArray()), [
            'status' => 'approved',
            'workflow_status' => $validated['lock'] ? 'locked' : 'approved',
            'approved_version_no' => $currentVersion,
            'locked_at' => $validated['lock'] ? now() : $designCustomization->locked_at,
            'production_status' => $validated['lock'] ? 'approved_for_handoff' : $designCustomization->production_status,
        ]))->save();

        $this->recordEvent($designCustomization, $request->user()->id, $validated['lock'] ? 'design_locked' : 'design_approved', $validated['lock'] ? 'Approved design locked for production handoff.' : 'Approved current design version.', null, [
            'version_no' => $currentVersion,
        ]);

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function updateOperationalStatus(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isAdmin() || $request->user()->isHr() || $request->user()->isStaff(), 403);
        $validated = $request->validate([
            'production_status' => ['required', 'in:ready_for_digitizing,ready_for_production,qc_review,production_handed_off'],
            'internal_note' => ['nullable', 'string'],
            'qc_note' => ['nullable', 'string'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_if($this->isLockedWithoutOverride($designCustomization, $request), 422, 'Locked designs require an override reason before editing.');

        $productionMeta = $designCustomization->production_meta_json ?? [];
        $productionMeta['internal_note'] = $validated['internal_note'] ?? ($productionMeta['internal_note'] ?? null);
        $productionMeta['qc_note'] = $validated['qc_note'] ?? ($productionMeta['qc_note'] ?? null);
        $productionMeta['last_override_reason'] = $validated['override_reason'] ?? ($productionMeta['last_override_reason'] ?? null);
        $productionMeta['operational_updated_by'] = $request->user()->only(['id','name']);
        $productionMeta['operational_updated_at'] = now()->toIso8601String();

        $designCustomization->forceFill([
            'production_status' => $validated['production_status'],
            'production_ready_at' => now(),
            'production_meta_json' => $productionMeta,
        ])->save();

        $this->recordEvent($designCustomization, $request->user()->id, 'production_status_updated', 'Updated production readiness to '.str_replace('_', ' ', $validated['production_status']).'.', $validated['internal_note'] ?? $validated['qc_note'] ?? null, [
            'production_status' => $validated['production_status'],
            'override_reason' => $validated['override_reason'] ?? null,
        ]);

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    public function createProductionPackage(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isAdmin() || $request->user()->isHr() || $request->user()->isStaff(), 403);
        $validated = $request->validate([
            'internal_note' => ['nullable', 'string'],
            'qc_note' => ['nullable', 'string'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
            'handoff' => ['nullable', 'boolean'],
        ]);

        $isApprovedAndLocked = (int) ($designCustomization->approved_version_no ?: 0) > 0 && ! empty($designCustomization->locked_at);
        abort_if(! $isApprovedAndLocked && empty($validated['override_reason']), 422, 'Production handoff requires an approved and locked design or an override reason.');

        $fresh = $designCustomization->fresh();
        $phaseFour = $this->phaseFourComputedFields($fresh->toArray());
        $fresh->forceFill($phaseFour)->save();
        $fresh = $fresh->fresh();

        $package = DesignProductionPackage::create([
            'design_customization_id' => $fresh->id,
            'created_by' => $request->user()->id,
            'version_no' => (int) ($fresh->approved_version_no ?: $fresh->current_version_no ?: 1),
            'package_no' => ((int) $fresh->productionPackages()->max('package_no')) + 1,
            'status' => ! empty($validated['handoff']) ? 'handed_off' : 'prepared',
            'preview_path' => $fresh->preview_path,
            'proof_summary_json' => $this->buildProofSummary($fresh),
            'design_metadata_json' => [
                'name' => $fresh->name,
                'garment_type' => $fresh->garment_type,
                'placement_area' => $fresh->placement_area,
                'dimensions_mm' => ['width' => $fresh->width_mm, 'height' => $fresh->height_mm],
                'editor_state' => $fresh->design_session_json,
                'preview_meta' => $fresh->preview_meta_json,
            ],
            'quote_basis_json' => $fresh->suggested_quote_basis_json,
            'thread_mapping_json' => $fresh->color_mapping_json,
            'risk_flags_json' => $fresh->risk_flags_json,
            'production_summary_json' => $this->buildProductionSummary($fresh),
            'internal_note' => $validated['internal_note'] ?? data_get($fresh->production_meta_json, 'internal_note'),
            'qc_note' => $validated['qc_note'] ?? data_get($fresh->production_meta_json, 'qc_note'),
            'handed_off_at' => ! empty($validated['handoff']) ? now() : null,
        ]);

        $productionMeta = $fresh->production_meta_json ?? [];
        if (! empty($validated['override_reason'])) {
            $productionMeta['last_override_reason'] = $validated['override_reason'];
            $productionMeta['override_actor'] = $request->user()->only(['id','name']);
        }
        $productionMeta['latest_package_summary'] = $package->production_summary_json;

        $fresh->forceFill([
            'latest_production_package_id' => $package->id,
            'production_status' => ! empty($validated['handoff']) ? 'production_handed_off' : 'ready_for_production',
            'production_ready_at' => now(),
            'production_meta_json' => $productionMeta,
        ])->save();

        $this->recordEvent($fresh, $request->user()->id, 'production_package_created', ! empty($validated['handoff']) ? 'Created and handed off production package.' : 'Created production package.', $validated['internal_note'] ?? $validated['qc_note'] ?? null, [
            'package_id' => $package->id,
            'package_no' => $package->package_no,
            'override_reason' => $validated['override_reason'] ?? null,
        ]);

        return response()->json($package->fresh('creator:id,name'));
    }

    public function unlockDesign(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        abort_unless($request->user()->isOwner() || $request->user()->isAdmin(), 403);
        $validated = $request->validate([
            'override_reason' => ['required', 'string', 'max:1000'],
        ]);

        $meta = $designCustomization->production_meta_json ?? [];
        $meta['last_override_reason'] = $validated['override_reason'];
        $meta['override_actor'] = $request->user()->only(['id','name']);
        $meta['override_at'] = now()->toIso8601String();

        $designCustomization->forceFill([
            'workflow_status' => 'approved',
            'locked_at' => null,
            'production_meta_json' => $meta,
        ])->save();

        $this->recordEvent($designCustomization, $request->user()->id, 'design_unlocked', 'Unlocked approved design for controlled edits.', $validated['override_reason'], []);

        return response()->json($designCustomization->fresh($this->workflowRelations()));
    }

    protected function captureSnapshot(DesignCustomization $customization, int $userId, string $summary, ?string $notes = null): DesignCustomizationSnapshot
    {
        $version = ((int) $customization->snapshots()->max('version_no')) + 1;
        $snapshot = DesignCustomizationSnapshot::create([
            'design_customization_id' => $customization->id,
            'version_no' => $version,
            'captured_by' => $userId,
            'change_summary' => $summary,
            'snapshot_json' => array_merge($customization->only([
                'name','garment_type','placement_area','fabric_type','width_mm','height_mm','color_count','stitch_count_estimate','complexity_level','special_styles_json','notes','artwork_path','preview_path','status','design_session_json','preview_meta_json','workflow_status','production_status','color_mapping_json','risk_flags_json','suggested_quote_basis_json'
            ]), ['revision_note' => $notes]),
            'pricing_snapshot_json' => $customization->pricing_breakdown_json,
        ]);
        $customization->forceFill(['current_version_no' => $version])->save();
        return $snapshot;
    }

    protected function recordEvent(DesignCustomization $designCustomization, ?int $actorId, string $type, string $summary, ?string $details = null, array $meta = []): void
    {
        DesignWorkflowEvent::create([
            'design_customization_id' => $designCustomization->id,
            'actor_user_id' => $actorId,
            'event_type' => $type,
            'summary' => $summary,
            'details' => $details,
            'event_meta_json' => $meta,
        ]);
    }

    protected function notifyShopSide(DesignCustomization $designCustomization, string $type, string $title, string $message): void
    {
        $shopUserIds = [];
        if ($designCustomization->order?->shop_id) {
            $shopUserIds = \App\Models\User::query()->where('shop_id', $designCustomization->order->shop_id)->pluck('id')->all();
        } elseif ($designCustomization->designPost?->selected_shop_id) {
            $shopUserIds = \App\Models\User::query()->where('shop_id', $designCustomization->designPost->selected_shop_id)->pluck('id')->all();
        }
        foreach (array_unique(array_filter($shopUserIds)) as $userId) {
            PlatformNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'category' => 'production',
                'priority' => 'medium',
                'title' => $title,
                'message' => $message,
                'action_label' => 'Open design',
                'reference_type' => 'design_customization',
                'reference_id' => $designCustomization->id,
                'channel' => 'web',
            ]);
        }
    }

    protected function buildProofSummary(DesignCustomization $designCustomization): array
    {
        $preview = $designCustomization->preview_meta_json ?? [];
        return [
            'client_name' => $designCustomization->user?->name,
            'design_reference' => 'DESIGN-'.$designCustomization->id,
            'garment_type' => $designCustomization->garment_type,
            'placement' => $designCustomization->placement_area,
            'dimensions' => trim(($designCustomization->width_mm ?: '—').' × '.($designCustomization->height_mm ?: '—').' mm'),
            'colors' => $designCustomization->color_count,
            'thread_mapping' => $designCustomization->color_mapping_json,
            'preview_image' => $designCustomization->preview_path,
            'estimated_stitch_count' => $designCustomization->stitch_count_estimate,
            'complexity' => $designCustomization->complexity_level,
            'notes' => $designCustomization->notes,
            'version_no' => (int) ($designCustomization->current_version_no ?: 1),
            'approval_status' => $designCustomization->workflow_status ?: $designCustomization->status,
            'readiness' => $preview['readiness'] ?? null,
            'warnings' => $preview['warnings'] ?? [],
            'suggested_quote_basis' => $designCustomization->suggested_quote_basis_json,
        ];
    }

    protected function buildProductionSummary(DesignCustomization $designCustomization): array
    {
        return [
            'design_reference' => 'DESIGN-'.$designCustomization->id,
            'approved_version_no' => (int) ($designCustomization->approved_version_no ?: $designCustomization->current_version_no ?: 1),
            'garment_type' => $designCustomization->garment_type,
            'placement_area' => $designCustomization->placement_area,
            'dimensions_mm' => ['width' => $designCustomization->width_mm, 'height' => $designCustomization->height_mm],
            'stitch_count_estimate' => $designCustomization->stitch_count_estimate,
            'complexity_level' => $designCustomization->complexity_level,
            'thread_summary' => $designCustomization->color_mapping_json,
            'risk_flags' => $designCustomization->risk_flags_json,
            'notes' => $designCustomization->notes,
            'linked_order_id' => $designCustomization->order_id,
            'linked_design_post_id' => $designCustomization->design_post_id,
            'quote_basis' => $designCustomization->suggested_quote_basis_json,
        ];
    }

    protected function phaseFourComputedFields(array $payload): array
    {
        $previewMeta = is_array($payload['preview_meta_json'] ?? null) ? $payload['preview_meta_json'] : [];
        $designSession = is_array($payload['design_session_json'] ?? null) ? $payload['design_session_json'] : [];
        $layers = collect($designSession['layers'] ?? [])->filter(fn ($layer) => is_array($layer))->values();
        $colors = $layers->flatMap(function ($layer) {
            return array_values(array_filter([
                data_get($layer, 'color'),
                data_get($layer, 'fill'),
                data_get($layer, 'stroke'),
                data_get($layer, 'meta.primaryColor'),
            ]));
        })->map(fn ($value) => strtoupper((string) $value))->filter()->unique()->values();
        $threadMapping = $colors->map(function ($hex, $index) {
            return [
                'hex' => $hex,
                'thread_code' => 'THR-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'thread_name' => $this->threadNameFromHex($hex),
                'family' => $this->threadFamilyFromHex($hex),
            ];
        })->values()->all();

        $width = (float) ($payload['width_mm'] ?? 0);
        $height = (float) ($payload['height_mm'] ?? 0);
        $stitches = (int) ($payload['stitch_count_estimate'] ?? data_get($previewMeta, 'stitch_estimate', 0));
        $complexity = strtolower(str_replace(' ', '_', (string) ($payload['complexity_level'] ?? data_get($previewMeta, 'complexity', 'medium'))));
        $colorCount = (int) ($payload['color_count'] ?? count($threadMapping) ?: count(data_get($previewMeta, 'palette', [])));
        $revisionCount = (int) ($payload['revision_count'] ?? 0);
        $placement = (string) ($payload['placement_area'] ?? 'left_chest');
        $garment = (string) ($payload['garment_type'] ?? 'polo');
        $isRush = (bool) ($payload['is_rush'] ?? false);
        $area = max(1, $width * $height);

        $complexityFactor = match ($complexity) {
            'very_high', 'premium' => 1.8,
            'high', 'complex' => 1.45,
            'medium', 'standard' => 1.2,
            default => 1.0,
        };
        $stitchFactor = round($stitches / 1000, 2);
        $placementSurcharge = in_array($placement, ['cap_front', 'cap_side', 'sleeve'], true) ? 85 : (in_array($placement, ['full_front', 'back'], true) ? 120 : 0);
        $garmentFactor = in_array($garment, ['cap', 'hoodie', 'jacket'], true) ? 1.18 : 1.0;
        $digitizingFee = round(max(180, 140 + ($stitches * 0.018 * $complexityFactor) + ($colorCount * 16)), 2);
        $revisionOverhead = round($revisionCount * 45, 2);
        $rushFee = $isRush ? round(max(120, $digitizingFee * 0.18), 2) : 0;
        $estimatedUnit = round((85 + ($stitches * 0.014) + ($colorCount * 18)) * $garmentFactor + $placementSurcharge, 2);
        $suggestedTotal = round($estimatedUnit + $digitizingFee + $revisionOverhead + $rushFee, 2);

        $riskFlags = [];
        foreach ((array) ($previewMeta['warnings'] ?? []) as $warning) {
            $riskFlags[] = ['level' => 'warning', 'message' => (string) $warning];
        }
        if ($colorCount >= 8) $riskFlags[] = ['level' => 'high', 'message' => 'High thread color count may require production simplification.'];
        if ($stitches >= 14000) $riskFlags[] = ['level' => 'high', 'message' => 'High stitch count may increase run time and heat buildup.'];
        if ($area >= 40000) $riskFlags[] = ['level' => 'medium', 'message' => 'Large embroidery area should be checked against placement stability.'];
        if (in_array($placement, ['cap_front', 'cap_side'], true) && $complexityFactor >= 1.45) $riskFlags[] = ['level' => 'high', 'message' => 'Cap embroidery with complex detail needs digitizer review.'];
        $riskFlags = collect($riskFlags)->unique(fn ($item) => $item['message'])->values()->all();

        return [
            'color_count' => max($colorCount, (int) ($payload['color_count'] ?? 0)),
            'color_mapping_json' => $threadMapping,
            'risk_flags_json' => $riskFlags,
            'suggested_quote_basis_json' => [
                'estimated_digitizing_fee' => $digitizingFee,
                'complexity_factor' => $complexityFactor,
                'stitch_effort_factor' => $stitchFactor,
                'placement_surcharge' => $placementSurcharge,
                'garment_factor' => $garmentFactor,
                'rush_fee' => $rushFee,
                'revision_overhead' => $revisionOverhead,
                'estimated_unit_price' => $estimatedUnit,
                'suggested_total' => $suggestedTotal,
                'color_count' => $colorCount,
            ],
            'production_meta_json' => array_merge(is_array($payload['production_meta_json'] ?? null) ? $payload['production_meta_json'] : [], [
                'quote_basis_updated_at' => now()->toIso8601String(),
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

    protected function threadFamilyFromHex(string $hex): string
    {
        $hex = strtoupper($hex);
        if (str_contains($hex, 'FF') && ! str_contains($hex, '00')) return 'light';
        if (str_contains($hex, '00')) return 'dark';
        return 'neutral';
    }

    protected function isLockedWithoutOverride(DesignCustomization $designCustomization, Request $request): bool
    {
        return ! empty($designCustomization->locked_at) && ! $request->filled('override_reason') && ($request->user()->isOwner() || $request->user()->isAdmin() || $request->user()->id === $designCustomization->user_id);
    }

    protected function workflowRelations(): array
    {
        return ['snapshots.actor', 'workflowEvents.actor', 'proofs.generator', 'proofs.responder', 'productionPackages.creator', 'latestProductionPackage'];
    }

    protected function authorizeView(DesignCustomization $designCustomization, $user): void
    {
        abort_unless($user && ($user->id === $designCustomization->user_id || $user->isAdmin() || $user->isOwner() || $user->isHr() || $user->isStaff()), 403);
    }

    protected function authorizeEdit(DesignCustomization $designCustomization, $user): void
    {
        abort_unless($user && ($user->id === $designCustomization->user_id || $user->isAdmin() || $user->isOwner() || $user->isHr() || $user->isStaff()), 403);
    }
}
