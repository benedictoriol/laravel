<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignCustomization;
use App\Models\DesignProof;
use App\Models\OrderProgressLog;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignProofController extends Controller
{
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
            'preview_file_path' => $validated['preview_file_path'],
            'annotated_notes' => $validated['annotated_notes'] ?? null,
            'pricing_snapshot_json' => $designCustomization->pricing_breakdown_json,
            'status' => 'pending_client',
            'expires_at' => now()->addDays(2),
        ]);
        $designCustomization->update(['status' => 'proof_ready']);

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
            'title' => 'Design proof ready',
            'message' => 'Proof #'.$proof->proof_no.' is ready for '.$designCustomization->name.'.',
            'reference_type' => 'design_proof',
            'reference_id' => $proof->id,
            'channel' => 'web',
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
            'approved_proof_id' => $validated['status'] === 'approved' ? $designProof->id : null,
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
        } else {
            PlatformNotification::create([
                'user_id' => $designProof->generated_by,
                'type' => 'design_proof_rejected',
                'title' => 'Design proof rejected',
                'message' => 'Proof #'.$designProof->proof_no.' needs another revision pass.',
                'reference_type' => 'design_proof',
                'reference_id' => $designProof->id,
                'channel' => 'web',
            ]);
        }
        return response()->json($designProof->fresh());
    }
}
