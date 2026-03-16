<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientPaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ClientPaymentMethod::where('user_id', $request->user()->id)
                ->orderByDesc('is_default')
                ->latest('id')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'method_type' => ['required', 'in:gcash,paymaya,bank,card,cod,other'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($validated['is_default'] ?? false) === true) {
            ClientPaymentMethod::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $method = ClientPaymentMethod::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return response()->json($method, 201);
    }

    public function update(Request $request, ClientPaymentMethod $paymentMethod): JsonResponse
    {
        abort_if($paymentMethod->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:120'],
            'method_type' => ['sometimes', 'in:gcash,paymaya,bank,card,cod,other'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:120'],
            'instructions' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($validated['is_default'] ?? false) === true) {
            ClientPaymentMethod::where('user_id', $request->user()->id)->where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
        }

        $paymentMethod->update($validated);

        return response()->json($paymentMethod->fresh());
    }

    public function destroy(Request $request, ClientPaymentMethod $paymentMethod): JsonResponse
    {
        abort_if($paymentMethod->user_id !== $request->user()->id, 403);
        $paymentMethod->delete();
        return response()->json(['message' => 'Payment method removed.']);
    }
}
