<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignPost;
use App\Models\JobPostApplication;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPostApplicationController extends Controller
{
    public function index(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isClient() && ! in_array($user->role, ['owner', 'hr', 'staff'], true)) {
            abort(403);
        }

        $query = JobPostApplication::where('design_post_id', $designPost->id)->with(['shop', 'owner']);
        if ($user->isClient()) {
            abort_if($designPost->client_user_id !== $user->id, 403);
        } elseif (! $user->isAdmin()) {
            $query->where('shop_id', $user->shop_id ?? 0);
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            abort(403, 'Only owners can apply to design posts.');
        }
        if (! $user->shop_id) {
            abort(422, 'Owner must be assigned to a shop.');
        }
        if ($designPost->status !== 'open') {
            abort(422, 'This design post is not open for applications.');
        }

        $validated = $request->validate([
            'proposed_price' => ['nullable', 'numeric', 'min:0'],
            'estimated_days' => ['nullable', 'integer', 'min:1'],
            'available_start_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string'],
            'sample_work_link' => ['nullable', 'string', 'max:255'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
        ]);

        $application = JobPostApplication::updateOrCreate(
            ['design_post_id' => $designPost->id, 'shop_id' => $user->shop_id],
            [
                'owner_user_id' => $user->id,
                'proposed_price' => $validated['proposed_price'] ?? null,
                'estimated_days' => $validated['estimated_days'] ?? null,
                'available_start_date' => $validated['available_start_date'] ?? null,
                'message' => $validated['message'] ?? null,
                'sample_work_link' => $validated['sample_work_link'] ?? null,
                'attachment_path' => $validated['attachment_path'] ?? null,
                'status' => 'pending',
                'applied_at' => now(),
            ]
        );

        PlatformNotification::create([
            'user_id' => $designPost->client_user_id,
            'type' => 'job_application_received',
            'title' => 'New shop proposal',
            'message' => 'A shop submitted a proposal for your design post "'.$designPost->title.'".',
            'reference_type' => 'design_post',
            'reference_id' => $designPost->id,
            'channel' => 'web',
        ]);

        return response()->json($application->load(['shop', 'owner']), 201);
    }

    public function update(Request $request, JobPostApplication $jobPostApplication): JsonResponse
    {
        $user = $request->user();
        if (! $user->isClient()) {
            abort(403, 'Only clients can respond to applications directly.');
        }

        $designPost = $jobPostApplication->designPost;
        abort_if($designPost->client_user_id !== $user->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:accepted,rejected,withdrawn'],
        ]);

        $jobPostApplication->update([
            'status' => $validated['status'],
            'responded_at' => now(),
        ]);

        return response()->json($jobPostApplication->fresh(['shop', 'owner', 'designPost']));
    }
}
