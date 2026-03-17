<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignCustomization;
use App\Models\DesignProof;
use App\Models\DesignWorkflowEvent;
use App\Models\OrderProgressLog;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ProductionOrchestrationService;

class DesignProofController extends Controller
{
    public function __construct(protected ProductionOrchestrationService $production) {}

    public function index(DesignCustomization $designCustomization): JsonResponse
    {
        return response()->json($designCustomization->proofs()->latest('proof_no')->get());
    }

    public function store(Request $request, DesignCustomization $designCustomization): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isOwner() || $request->user()->isHr() || $request->user()->isStaff(), 403);
        $validated = $request->validate([
            'preview_file_path' => ['required', 'string', 'max:255'],
            'annotated_notes' => ['nullable', 'string'],
        ]);

        $designCustomization->proofs()->where('status', 'pending_client')->update(['status' => 'superseded']);

        $proof = DesignProof::create([
            'design_customization_id' => $designCustomization->id,
            'proof_no' => ((int) $designCustomization->proofs()->max('proof_no')) + 1,
            'generated_by' => $request->user()->id,
            'version_no' => (int) ($designCustomization->current_version_no ?: ($designCustomization->snapshots()->max('version_no') ?: 1)),
            'preview_file_path' => $validated['preview_file_path'],
            'annotated_notes' => $validated['annotated_notes'] ?? null,
            'pricing_snapshot_json' => $designCustomization->pricing_breakdown_json,
            'proof_summary_json' => [
                'client_name' => $designCustomization->user?->name,
                'design_reference' => 'DESIGN-'.$designCustomization->id,
                'garment_type' => $designCustomization->garment_type,
                'placement' => $designCustomization->placement_area,
                'dimensions' => trim(($designCustomization->width_mm ?: '—').' × '.($designCustomization->height_mm ?: '—').' mm'),
                'colors' => $designCustomization->color_count,
                'preview_image' => $designCustomization->preview_path,
                'estimated_stitch_count' => $designCustomization->stitch_count_estimate,
                'complexity' => $designCustomization->complexity_level,
                'notes' => $designCustomization->notes,
                'version_no' => (int) ($designCustomization->current_version_no ?: 1),
                'approval_status' => 'proof_ready',
            ],
            'status' => 'pending_client',
            'expires_at' => now()->addDays(2),
        ]);
        $designCustomization->update(['status' => 'proof_ready', 'workflow_status' => 'proof_ready']);

        if ($designCustomization->order_id) {
            OrderProgressLog::create([
                'order_id' => $designCustomization->order_id,
                'status' => 'proof_generated',
                'title' => 'Design proof generated',
                'description' => 'Proof #'.$proof->proof_no.' was generated for '.$designCustomization->name.'.',
                'actor_user_id' => $request->user()->id,
            ]);
        }
        PlatformNotification::create([
            'user_id' => $designCustomization->user_id,
            'type' => 'design_proof_ready',
            'category' => 'production',
            'priority' => 'medium',
            'title' => 'Design proof ready',
            'message' => 'Proof #'.$proof->proof_no.' is ready for '.$designCustomization->name.'.',
            'action_label' => 'Review proof',
            'reference_type' => 'design_proof',
            'reference_id' => $proof->id,
            'channel' => 'web',
        ]);
        DesignWorkflowEvent::create([
            'design_customization_id' => $designCustomization->id,
            'actor_user_id' => $request->user()->id,
            'event_type' => 'proof_generated',
            'summary' => 'Generated proof #'.$proof->proof_no.'.',
            'details' => $validated['annotated_notes'] ?? null,
            'event_meta_json' => ['proof_id' => $proof->id, 'version_no' => $proof->version_no],
        ]);
        return response()->json($proof, 201);
    }

    public function respond(Request $request, DesignCustomization $designCustomization, DesignProof $designProof): JsonResponse
    {
        abort_unless($designProof->design_customization_id === $designCustomization->id, 404);
        abort_if($designProof->status !== 'pending_client', 422, 'This proof is no longer awaiting client response.');
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'annotated_notes' => ['nullable', 'string'],
        ]);
        $designProof->update([
            'status' => $validated['status'],
            'annotated_notes' => $validated['annotated_notes'] ?? $designProof->annotated_notes,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);
        $designCustomization->update([
            'status' => $validated['status'] === 'approved' ? 'approved' : 'estimated',
            'workflow_status' => $validated['status'] === 'approved' ? 'approved' : 'revision_requested',
            'approved_proof_id' => $validated['status'] === 'approved' ? $designProof->id : null,
            'approved_version_no' => $validated['status'] === 'approved' ? $designProof->version_no : $designCustomization->approved_version_no,
            'last_revision_requested_at' => $validated['status'] === 'rejected' ? now() : $designCustomization->last_revision_requested_at,
        ]);
        if ($validated['status'] === 'approved') {
            $designCustomization->proofs()->where('id', '!=', $designProof->id)->where('status', 'pending_client')->update(['status' => 'superseded']);
            if ($designCustomization->order_id) {
                OrderProgressLog::create([
                    'order_id' => $designCustomization->order_id,
                    'status' => 'proof_approved',
                    'title' => 'Design proof approved',
                    'description' => 'Approved proof #'.$designProof->proof_no.' is now the canonical production proof.',
                    'actor_user_id' => $request->user()->id,
                ]);
            }
            PlatformNotification::create([
                'user_id' => $designProof->generated_by,
                'type' => 'design_proof_approved',
                'category' => 'production',
                'priority' => 'medium',
                'title' => 'Proof approved',
                'message' => 'Client approved proof #'.$designProof->proof_no.'. You can continue with quote and production preparation.',
                'action_label' => 'Open proof',
                'reference_type' => 'design_proof',
                'reference_id' => $designProof->id,
                'channel' => 'web',
            ]);
        } else {
            if ($designCustomization->order) {
                $this->production->routeException($designCustomization->order, 'proof_rejected', 'Client rejected proof #'.$designProof->proof_no.'. '.($validated['annotated_notes'] ?? ''), 'medium');
            }
            PlatformNotification::create([
                'user_id' => $designProof->generated_by,
                'type' => 'design_proof_rejected',
                'category' => 'production',
                'priority' => 'high',
                'title' => 'Design proof rejected',
                'message' => 'Proof #'.$designProof->proof_no.' needs another revision pass.',
                'action_label' => 'Revise proof',
                'reference_type' => 'design_proof',
                'reference_id' => $designProof->id,
                'channel' => 'web',
            ]);
        }
        DesignWorkflowEvent::create([
            'design_customization_id' => $designCustomization->id,
            'actor_user_id' => $request->user()->id,
            'event_type' => $validated['status'] === 'approved' ? 'design_approved' : 'revision_requested',
            'summary' => $validated['status'] === 'approved' ? 'Client approved proof #'.$designProof->proof_no.'.' : 'Client rejected proof #'.$designProof->proof_no.'.',
            'details' => $validated['annotated_notes'] ?? null,
            'event_meta_json' => ['proof_id' => $designProof->id, 'version_no' => $designProof->version_no],
        ]);
        return response()->json($designProof->fresh());
    }
}
