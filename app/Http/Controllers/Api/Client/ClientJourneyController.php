<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Services\ClientJourneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientJourneyController extends Controller
{
    public function __construct(private ClientJourneyService $journeyService)
    {
    }

    public function requestQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'service_selection' => ['required', 'in:logo_embroidery,name_embroidery,patch_embroidery,uniform_embroidery,cap_embroidery,custom_design_embroidery'],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'garment_type' => ['required', 'string', 'max:100'],
            'placement_area' => ['required', 'string', 'max:100'],
            'fabric_type' => ['required', 'string', 'max:100'],
            'width_mm' => ['required', 'numeric', 'min:1'],
            'height_mm' => ['required', 'numeric', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'color_count' => ['required', 'integer', 'min:1'],
            'stitch_count_estimate' => ['nullable', 'integer', 'min:1'],
            'complexity_level' => ['required', 'in:simple,standard,complex,premium'],
            'upload_design_file' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string'],
            'is_rush' => ['nullable', 'boolean'],
            'thread_palette_json' => ['nullable', 'array'],
            'design_json' => ['nullable', 'array'],
            'preview_svg' => ['nullable', 'string'],
        ]);

        $result = $this->journeyService->submitQuoteRequest($request->user(), array_merge($validated, [
            'thread_palette_json' => $validated['thread_palette_json'] ?? [],
            'design_json' => $validated['design_json'] ?? [],
        ]));

        return response()->json([
            'message' => 'Design, quote request, and order draft were created successfully.',
            'data' => $result,
        ], 201);
    }
}
