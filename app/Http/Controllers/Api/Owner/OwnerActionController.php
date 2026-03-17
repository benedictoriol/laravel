<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Services\OwnerExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerActionController extends Controller
{
    public function __construct(protected OwnerExecutionService $actions) {}

    public function reassignStaff(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'assignment_type' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($this->actions->reassignStaff($user->shop, $order, $user, (int) $validated['staff_id'], $validated['assignment_type'], $validated['notes'] ?? null));
    }

    public function approveProductionPlan(Request $request, Order $order): JsonResponse
    {
        return response()->json($this->actions->approveProductionPlan($request->user()->shop, $order, $request->user()));
    }

    public function createRestockRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => ['required', 'integer', 'exists:raw_materials,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($this->actions->createRestockRequest($request->user()->shop, $request->user(), (int) $validated['material_id'], isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null, isset($validated['quantity']) ? (float) $validated['quantity'] : null, $validated['notes'] ?? null));
    }

    public function followUpPayment(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'extend_due_date' => ['nullable', 'boolean'],
        ]);

        return response()->json($this->actions->followUpPayment($request->user()->shop, $order, $request->user(), $validated['notes'] ?? null, (bool) ($validated['extend_due_date'] ?? false)));
    }

    public function escalateOrder(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate(['notes' => ['nullable', 'string']]);
        return response()->json($this->actions->escalateOrder($request->user()->shop, $order, $request->user(), $validated['notes'] ?? null));
    }

    public function pauseProduction(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate(['notes' => ['nullable', 'string']]);
        return response()->json($this->actions->pauseProduction($request->user()->shop, $order, $request->user(), $validated['notes'] ?? null));
    }

    public function resumeProduction(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate(['notes' => ['nullable', 'string']]);
        return response()->json($this->actions->resumeProduction($request->user()->shop, $order, $request->user(), $validated['notes'] ?? null));
    }

    public function resolveAlert(Request $request, OperationalAlert $alert): JsonResponse
    {
        return response()->json($this->actions->resolveAlert($request->user()->shop, $alert, $request->user()));
    }

    public function snoozeAlert(Request $request, OperationalAlert $alert): JsonResponse
    {
        $validated = $request->validate(['hours' => ['nullable', 'integer', 'min:1', 'max:168']]);
        return response()->json($this->actions->snoozeAlert($request->user()->shop, $alert, $request->user(), (int) ($validated['hours'] ?? 6)));
    }

    public function createQualityCheck(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'result' => ['required', 'string', 'max:50'],
            'issue_notes' => ['nullable', 'string'],
            'rework_required' => ['nullable', 'boolean'],
            'action_taken' => ['nullable', 'string'],
        ]);

        return response()->json($this->actions->createQualityCheck($request->user()->shop, $order, $request->user(), $validated['result'], $validated['issue_notes'] ?? null, (bool) ($validated['rework_required'] ?? false), $validated['action_taken'] ?? null));
    }

    public function markPackageReady(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate(['notes' => ['nullable', 'string']]);
        return response()->json($this->actions->markPackageReady($request->user()->shop, $order, $request->user(), $validated['notes'] ?? null));
    }

    public function assignCourier(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'courier_name' => ['required', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json($this->actions->assignCourier($request->user()->shop, $order, $request->user(), $validated['courier_name'], $validated['tracking_number'] ?? null, $validated['notes'] ?? null));
    }

    public function maintainNotifications(Request $request): JsonResponse
    {
        return response()->json($this->actions->maintainNotificationLifecycle($request->user()->shop, $request->user()));
    }
}
