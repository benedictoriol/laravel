<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientSavedAddress;
use App\Models\ClientProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = ClientProfile::firstOrCreate(['user_id' => $user->id]);
        return response()->json($profile->load('addresses'));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = ClientProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'default_address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'preferred_contact_method' => ['nullable', 'in:email,phone,chat'],
            'mobile_push_enabled' => ['nullable', 'boolean'],
            'organization_name' => ['nullable', 'string', 'max:180'],
            'preferred_fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'saved_measurements_json' => ['nullable', 'array'],
            'default_garment_preferences_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $profile->update($validated);
        return response()->json($profile->fresh()->load('addresses'));
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:60'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'address_line' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
            'delivery_notes' => ['nullable', 'string'],
        ]);

        if (!empty($validated['is_default'])) {
            $profile->addresses()->update(['is_default' => false]);
        }

        $address = $profile->addresses()->create($validated);

        if ($address->is_default) {
            $profile->update([
                'default_address' => $address->address_line,
                'postal_code' => $address->postal_code,
            ]);
        }

        return response()->json($address, 201);
    }

    public function updateAddress(Request $request, ClientSavedAddress $address): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        abort_unless((int) $address->client_profile_id === (int) $profile->id, 404);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:60'],
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'address_line' => ['sometimes', 'required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
            'delivery_notes' => ['nullable', 'string'],
        ]);

        if (($validated['is_default'] ?? false) === true) {
            $profile->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        if ($address->is_default) {
            $profile->update([
                'default_address' => $address->address_line,
                'postal_code' => $address->postal_code,
            ]);
        }

        return response()->json($address->fresh());
    }

    public function deleteAddress(Request $request, ClientSavedAddress $address): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        abort_unless((int) $address->client_profile_id === (int) $profile->id, 404);
        $address->delete();
        return response()->json(['message' => 'Address removed successfully.']);
    }
}
