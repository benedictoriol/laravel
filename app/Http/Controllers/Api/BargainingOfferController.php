<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BargainingOffer;
use App\Models\DesignPost;
use App\Models\JobPostApplication;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BargainingOfferController extends Controller
{
    public function index(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if ($user->isClient()) {
            abort_if($designPost->client_user_id !== $user->id, 403);
        } elseif (! $user->isAdmin()) {
            abort_unless(($user->shop_id && $designPost->applications()->where('shop_id', $user->shop_id)->exists()) || $designPost->selected_shop_id === ($user->shop_id ?? 0), 403);
        }
        return response()->json(BargainingOffer::where('design_post_id', $designPost->id)->with(['offeredBy', 'application.shop', 'children'])->latest('id')->get());
    }

    public function store(Request $request, DesignPost $designPost): JsonResponse
    {
        $validated = $request->validate([
            'job_post_application_id' => ['nullable', 'integer', 'exists:job_post_applications,id'],
            'parent_offer_id' => ['nullable', 'integer', 'exists:bargaining_offers,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'estimated_days' => ['nullable', 'integer', 'min:1'],
            'message' => ['nullable', 'string'],
        ]);
        $user = $request->user();
        $applicationId = $validated['job_post_application_id'] ?? null;
        if ($user->isClient()) {
            abort_if($designPost->client_user_id !== $user->id, 403);
        } elseif ($user->isOwner()) {
            $application = JobPostApplication::where('design_post_id', $designPost->id)->where('shop_id', $user->shop_id ?? 0)->first();
            $applicationId = $application?->id;
        } else {
            abort(403, 'Only clients and owners can bargain.');
        }

        $parent = !empty($validated['parent_offer_id']) ? BargainingOffer::find($validated['parent_offer_id']) : null;
        $offer = BargainingOffer::create([
            'design_post_id' => $designPost->id,
            'job_post_application_id' => $applicationId,
            'parent_offer_id' => $validated['parent_offer_id'] ?? null,
            'offered_by_user_id' => $user->id,
            'amount' => $validated['amount'],
            'estimated_days' => $validated['estimated_days'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
            'negotiation_round' => $parent ? ((int) $parent->negotiation_round + 1) : 1,
        ]);

        if ($parent) {
            $parent->update([
                'status' => 'countered',
                'responded_by' => $user->id,
                'responded_at' => now(),
            ]);
        }

        $notifyUserId = $user->isClient()
            ? JobPostApplication::find($applicationId)?->owner_user_id
            : $designPost->client_user_id;
        if ($notifyUserId) {
            PlatformNotification::create([
                'user_id' => $notifyUserId,
                'type' => 'bargaining_offer_created',
                'title' => 'New bargaining offer',
                'message' => 'A new bargaining offer was submitted for “'.$designPost->title.'”.',
                'reference_type' => 'bargaining_offer',
                'reference_id' => $offer->id,
                'channel' => 'web',
            ]);
        }
        return response()->json($offer->load(['offeredBy', 'application.shop', 'children']), 201);
    }

    public function respond(Request $request, BargainingOffer $bargainingOffer): JsonResponse
    {
        abort_if($bargainingOffer->status !== 'pending', 422, 'Only pending offers can be responded to.');
        $validated = $request->validate([
            'status' => ['required', 'in:accepted,rejected,countered'],
        ]);
        $bargainingOffer->update([
            'status' => $validated['status'],
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);
        if ($validated['status'] === 'accepted' && $bargainingOffer->application) {
            $bargainingOffer->application->update([
                'proposed_price' => $bargainingOffer->amount,
                'estimated_days' => $bargainingOffer->estimated_days,
                'status' => 'accepted',
                'responded_at' => now(),
            ]);
            $bargainingOffer->designPost?->update([
                'status' => 'shop_selected',
                'selected_shop_id' => $bargainingOffer->application->shop_id,
            ]);
            BargainingOffer::where('design_post_id', $bargainingOffer->design_post_id)
                ->where('id', '!=', $bargainingOffer->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);
        }
        if ($validated['status'] === 'rejected') {
            PlatformNotification::create([
                'user_id' => $bargainingOffer->offered_by_user_id,
                'type' => 'bargaining_offer_rejected',
                'title' => 'Bargaining offer rejected',
                'message' => 'Your bargaining offer #'.$bargainingOffer->id.' was rejected.',
                'reference_type' => 'bargaining_offer',
                'reference_id' => $bargainingOffer->id,
                'channel' => 'web',
            ]);
        }
        return response()->json($bargainingOffer->fresh(['offeredBy', 'application.shop', 'children']));
    }
}
