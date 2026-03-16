<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerWorkspaceController extends Controller
{
    public function __construct(protected OwnerWorkspaceService $workspace) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner' && $user->shop_id, 403, 'Owner workspace is only available to an owner with an assigned shop.');
        return response()->json($this->workspace->build($user->shop));
    }
}
