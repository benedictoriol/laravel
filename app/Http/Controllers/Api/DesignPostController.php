<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DesignPost;
use App\Models\JobPostApplication;
use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DesignPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DesignPost::query()->with(['client', 'selectedShop', 'applications.shop', 'applications.owner']);
        $user = $request->user();

        if ($user->isClient()) {
            $query->where('client_user_id', $user->id);
        } elseif (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('selected_shop_id')
                  ->orWhere('selected_shop_id', $user->shop_id ?? 0)
                  ->orWhereHas('applications', fn ($aq) => $aq->where('shop_id', $user->shop_id ?? 0));
            });
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isClient()) {
            abort(403, 'Only clients can create design posts.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'design_type' => ['nullable', 'in:logo,uniform,cap,patch,custom_art,digitizing,other'],
            'fabric_type' => ['nullable', 'string', 'max:100'],
            'garment_type' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'target_budget' => ['nullable', 'numeric', 'min:0'],
            'deadline_date' => ['nullable', 'date'],
            'visibility' => ['nullable', 'in:public,private,closed'],
            'notes' => ['nullable', 'string'],
            'reference_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,ai,eps,svg,zip,rar,7z,doc,docx', 'max:15360'],
            'reference_file_path' => ['nullable', 'string', 'max:255'],
        ]);

        $referencePath = $validated['reference_file_path'] ?? null;
        if ($request->hasFile('reference_file')) {
            $referencePath = $request->file('reference_file')->store('design-posts', 'public');
        }

        $designPost = DesignPost::create([
            'client_user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'cavite_location_id' => $validated['cavite_location_id'] ?? null,
            'design_type' => $validated['design_type'] ?? 'custom_art',
            'fabric_type' => $validated['fabric_type'] ?? null,
            'garment_type' => $validated['garment_type'] ?? null,
            'quantity' => $validated['quantity'] ?? 1,
            'target_budget' => $validated['target_budget'] ?? null,
            'deadline_date' => $validated['deadline_date'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
            'notes' => $validated['notes'] ?? null,
            'reference_file_path' => $referencePath,
            'status' => 'open',
        ]);

        return response()->json($designPost->load(['client', 'selectedShop', 'applications.shop', 'applications.owner']), 201);
    }

    public function show(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if ($user->isClient() && $designPost->client_user_id !== $user->id) {
            abort(403, 'Unauthorized design post access.');
        }

        return response()->json($designPost->load(['client', 'selectedShop', 'applications.shop', 'applications.owner']));
    }

    public function update(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if (! $user->isClient() || $designPost->client_user_id !== $user->id) {
            abort(403, 'Only the owning client can update this design post.');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'required', 'string'],
            'target_budget' => ['nullable', 'numeric', 'min:0'],
            'deadline_date' => ['nullable', 'date'],
            'visibility' => ['nullable', 'in:public,private,closed'],
            'status' => ['nullable', 'in:open,under_review,shop_selected,converted_to_order,cancelled,completed'],
            'notes' => ['nullable', 'string'],
            'reference_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,ai,eps,svg,zip,rar,7z,doc,docx', 'max:15360'],
            'reference_file_path' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('reference_file')) {
            if ($designPost->reference_file_path) {
                Storage::disk('public')->delete($designPost->reference_file_path);
            }
            $validated['reference_file_path'] = $request->file('reference_file')->store('design-posts', 'public');
        }

        if (($validated['status'] ?? null) === 'closed') {
            $validated['closed_at'] = now();
        }

        $designPost->update($validated);

        return response()->json($designPost->fresh(['client', 'selectedShop', 'applications.shop', 'applications.owner']));
    }

    public function selectShop(Request $request, DesignPost $designPost): JsonResponse
    {
        $user = $request->user();
        if (! $user->isClient() || $designPost->client_user_id !== $user->id) {
            abort(403, 'Only the owning client can select a shop.');
        }

        $validated = $request->validate([
            'application_id' => ['nullable', 'integer', 'exists:job_post_applications,id'],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'convert_to_order' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['application_id']) && empty($validated['shop_id'])) {
            abort(422, 'A shop or proposal must be selected.');
        }

        $application = null;
        if (! empty($validated['application_id'])) {
            $application = JobPostApplication::where('design_post_id', $designPost->id)->findOrFail($validated['application_id']);
        } else {
            $shop = Shop::findOrFail($validated['shop_id']);
            $application = JobPostApplication::firstOrCreate(
                ['design_post_id' => $designPost->id, 'shop_id' => $shop->id],
                [
                    'owner_user_id' => $shop->owner_user_id,
                    'status' => 'accepted',
                    'applied_at' => now(),
                    'responded_at' => now(),
                    'message' => 'Selected directly by client for proofing and quotation request.',
                ]
            );
        }

        DB::transaction(function () use ($designPost, $application, $validated) {
            JobPostApplication::where('design_post_id', $designPost->id)
                ->where('id', '!=', $application->id)
                ->update(['status' => 'rejected', 'responded_at' => now()]);

            $application->update(['status' => 'accepted', 'responded_at' => now()]);

            $updates = [
                'selected_shop_id' => $application->shop_id,
                'status' => 'shop_selected',
            ];

            if (($validated['convert_to_order'] ?? false) === true) {
                $order = Order::create([
                    'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                    'client_user_id' => $designPost->client_user_id,
                    'shop_id' => $application->shop_id,
                    'source_design_post_id' => $designPost->id,
                    'order_type' => 'marketplace_job',
                    'status' => 'pending',
                    'current_stage' => 'intake',
                    'payment_status' => 'unpaid',
                    'fulfillment_type' => 'pickup',
                    'subtotal' => $application->proposed_price ?? 0,
                    'total_amount' => $application->proposed_price ?? 0,
                    'due_date' => $designPost->deadline_date,
                    'customer_notes' => $designPost->notes,
                ]);

                $updates['converted_order_id'] = $order->id;
                $updates['status'] = 'converted_to_order';
            }

            $designPost->update($updates);

            if ($application->owner_user_id) {
                PlatformNotification::create([
                    'user_id' => $application->owner_user_id,
                    'type' => 'design_post_selected',
                    'title' => 'Client selected your shop',
                    'message' => 'A client selected your shop for “'.$designPost->title.'”.',
                    'reference_type' => 'design_post',
                    'reference_id' => $designPost->id,
                    'channel' => 'web',
                ]);
            }
        });

        return response()->json($designPost->fresh(['client', 'selectedShop', 'applications.shop', 'applications.owner']));
    }
}
