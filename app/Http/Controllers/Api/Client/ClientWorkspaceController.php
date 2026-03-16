<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientWorkspaceController extends Controller
{
    public function __construct(private ClientWorkspaceService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->build($request->user()));
    }
}
