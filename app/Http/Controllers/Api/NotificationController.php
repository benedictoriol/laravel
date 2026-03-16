<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            PlatformNotification::where('user_id', $request->user()->id)
                ->latest('id')
                ->get()
        );
    }

    public function markRead(Request $request, PlatformNotification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json($notification->fresh());
    }
}
