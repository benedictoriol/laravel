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
        $profile = ClientProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'registration_date' => optional($user->created_at)->toDateString(),
            ]
        );

        if (! $profile->registration_date && $user->created_at) {
            $profile->forceFill(['registration_date' => $user->created_at->toDateString()])->save();
        }

        if (! $profile->email && $user->email) {
            $profile->forceFill(['email' => $user->email])->save();
        }

        return response()->json([
            'profile' => $profile->load('addresses'),
            'address_options' => $this->addressOptions(),
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json($this->addressOptions());
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = ClientProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone_number' => ['nullable', 'regex:/^(?:\+63\d{10}|0\d{10}|\(0\d{2}\)\s?\d{7}|0\d{2}\s?\d{7})$/'],
            'billing_contact_name' => ['nullable', 'string', 'max:180'],
            'billing_phone' => ['nullable', 'regex:/^(?:\+63\d{10}|0\d{10}|\(0\d{2}\)\s?\d{7}|0\d{2}\s?\d{7})$/'],
            'billing_email' => ['nullable', 'email', 'max:180'],
            'default_payment_method' => ['nullable', 'string', 'max:100'],
            'registration_date' => ['nullable', 'date'],
        ]);

        if (! empty($validated['email']) && $validated['email'] !== $user->email) {
            $user->forceFill(['email' => $validated['email']])->save();
        }

        $validated['registration_date'] = $profile->registration_date ?: ($validated['registration_date'] ?? optional($user->created_at)->toDateString());
        $profile->update($validated);

        return response()->json([
            'profile' => $profile->fresh()->load('addresses'),
            'address_options' => $this->addressOptions(),
        ]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $validated = $this->validateAddress($request, false);
        $validated['country'] = 'Philippines';
        $validated['province'] = 'Cavite';

        if (! empty($validated['is_default'])) {
            $profile->addresses()->update(['is_default' => false]);
        }

        $address = $profile->addresses()->create($validated);

        return response()->json($address->fresh(), 201);
    }

    public function updateAddress(Request $request, ClientSavedAddress $address): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        abort_unless((int) $address->client_profile_id === (int) $profile->id, 404);

        $validated = $this->validateAddress($request, true);
        $validated['country'] = 'Philippines';
        $validated['province'] = 'Cavite';

        if (($validated['is_default'] ?? false) === true) {
            $profile->addresses()->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json($address->fresh());
    }

    public function deleteAddress(Request $request, ClientSavedAddress $address): JsonResponse
    {
        $profile = ClientProfile::firstOrCreate(['user_id' => $request->user()->id]);
        abort_unless((int) $address->client_profile_id === (int) $profile->id, 404);
        $address->delete();
        return response()->json(['message' => 'Address removed successfully.']);
    }

    protected function validateAddress(Request $request, bool $partial): array
    {
        $cities = array_keys($this->addressOptions()['cities']);

        return $request->validate([
            'label' => [$partial ? 'sometimes' : 'required', 'string', 'max:60'],
            'recipient_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:150'],
            'recipient_phone' => [$partial ? 'sometimes' : 'required', 'regex:/^(?:\+63\d{10}|0\d{10}|\(0\d{2}\)\s?\d{7}|0\d{2}\s?\d{7})$/'],
            'city_municipality' => [$partial ? 'sometimes' : 'required', 'string', 'in:'.implode(',', $cities)],
            'barangay' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'house_street' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'other_house_information' => ['nullable', 'string', 'max:255'],
            'postal_code' => [$partial ? 'sometimes' : 'required', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    protected function addressOptions(): array
    {
        return [
            'country' => 'Philippines',
            'province' => 'Cavite',
            'cities' => [
                'Bacoor' => ['Alima', 'Aniban I', 'Aniban II', 'Aniban III', 'Aniban IV', 'Aniban V', 'Banalo', 'Bayanan', 'Daang Bukid', 'Digman', 'Dulong Bayan', 'Habay I', 'Habay II', 'Kaingen', 'Ligas I', 'Ligas II', 'Ligas III', 'Mabolo I', 'Mabolo II', 'Mabolo III', 'Maliksi I', 'Maliksi II', 'Maliksi III', 'Mambog I', 'Mambog II', 'Mambog III', 'Mambog IV', 'Mambog V', 'Molino I', 'Molino II', 'Molino III', 'Molino IV', 'Molino V', 'Molino VI', 'Molino VII', 'Niog I', 'Niog II', 'Niog III', 'Panapaan I', 'Panapaan II', 'Panapaan III', 'Panapaan IV', 'Panapaan V', 'Panapaan VI', 'Panapaan VII', 'Queens Row Central', 'Queens Row East', 'Queens Row West', 'Real I', 'Real II', 'Salinas I', 'Salinas II', 'Salinas III', 'Salinas IV', 'San Nicolas I', 'San Nicolas II', 'San Nicolas III', 'Sineguelasan', 'Talaba I', 'Talaba II', 'Talaba III', 'Talaba IV', 'Talaba V', 'Talaba VI', 'Talaba VII', 'Zapote I', 'Zapote II', 'Zapote III', 'Zapote IV', 'Zapote V'],
                'Dasmariñas' => ['Bagong Bayan', 'Bucal', 'Burol I', 'Burol II', 'Burol III', 'Datu Esmael', 'Emmanuel Bergado I', 'Emmanuel Bergado II', 'Fatima I', 'Fatima II', 'Fatima III', 'H-2', 'Langkaan I', 'Langkaan II', 'Luzviminda I', 'Luzviminda II', 'Paliparan I', 'Paliparan II', 'Paliparan III', 'Sabang', 'Salawag', 'Salitran I', 'Salitran II', 'Salitran III', 'Salitran IV', 'Sampaloc I', 'Sampaloc II', 'Sampaloc III', 'Sampaloc IV', 'Sampaloc V', 'San Agustin I', 'San Agustin II', 'San Agustin III', 'San Andres I', 'San Andres II', 'San Antonio De Padua I', 'San Antonio De Padua II', 'San Dionisio', 'San Esteban', 'San Francisco I', 'San Francisco II', 'San Isidro Labrador I', 'San Isidro Labrador II', 'San Jose', 'San Juan', 'San Lorenzo Ruiz I', 'San Lorenzo Ruiz II', 'San Luis I', 'San Luis II', 'San Manuel I', 'San Manuel II', 'San Mateo', 'San Miguel', 'San Nicolas I', 'San Nicolas II', 'San Roque', 'San Simon', 'Santa Cristina I', 'Santa Cristina II', 'Santa Cruz I', 'Santa Cruz II', 'Santa Fe', 'Santa Lucia', 'Santa Maria', 'Santo Cristo', 'Santo Niño I', 'Santo Niño II', 'Victoria Reyes', 'Zone I', 'Zone I-A', 'Zone II', 'Zone III', 'Zone IV'],
                'Imus' => ['Alapan I-A', 'Alapan I-B', 'Alapan I-C', 'Alapan II-A', 'Alapan II-B', 'Anabu I-A', 'Anabu I-B', 'Anabu I-C', 'Anabu I-D', 'Anabu I-E', 'Anabu I-F', 'Anabu I-G', 'Anabu II-A', 'Anabu II-B', 'Anabu II-C', 'Anabu II-D', 'Anabu II-E', 'Anabu II-F', 'Bagong Silang', 'Bayan Luma I', 'Bayan Luma II', 'Bayan Luma III', 'Bayan Luma IV', 'Bayan Luma V', 'Bucandala I', 'Bucandala II', 'Bucandala III', 'Bucandala IV', 'Carsadang Bago I', 'Carsadang Bago II', 'Magdalo', 'Maharlika', 'Malagasang I-A', 'Malagasang I-B', 'Malagasang I-C', 'Malagasang I-D', 'Malagasang I-E', 'Malagasang I-F', 'Malagasang I-G', 'Malagasang II-A', 'Malagasang II-B', 'Medicion I-A', 'Medicion I-B', 'Medicion I-C', 'Medicion I-D', 'Medicion II-A', 'Medicion II-B', 'Medicion II-C', 'Medicion II-D', 'Poblacion I-A', 'Poblacion I-B', 'Poblacion I-C', 'Poblacion II-A', 'Poblacion II-B', 'Poblacion III-A', 'Poblacion III-B', 'Poblacion IV-A', 'Poblacion IV-B', 'Poblacion IV-C', 'Poblacion IV-D', 'Tanzang Luma I', 'Tanzang Luma II', 'Tanzang Luma III', 'Tanzang Luma IV'],
                'General Trias' => ['Bacao I', 'Bacao II', 'Bagumbayan', 'Biclatan', 'Buenavista I', 'Buenavista II', 'Buenavista III', 'Corregidor', 'Dulong Bayan', 'Governor Ferrer', 'Javalera', 'Manggahan', 'Navarro', 'Panungyanan', 'Pasong Camachile I', 'Pasong Camachile II', 'Pinagtipunan', 'Prinza', 'Sampalucan', 'San Francisco', 'San Gabriel', 'San Juan I', 'San Juan II', 'Santa Clara', 'Santiago', 'Tapia', 'Tejero', 'Vibora'],
                'Silang' => ['Acacia', 'Adlas', 'Anahaw I', 'Anahaw II', 'Balite I', 'Balite II', 'Balubad', 'Banaba', 'Batas', 'Biluso', 'Bucal', 'Buho', 'Bunga', 'Calubcub', 'Carmen', 'Hoyo', 'Hukay', 'Iba', 'Inchican', 'Kaong', 'Lalaan I', 'Lalaan II', 'Litlit', 'Lucsuhin', 'Lumil', 'Maguyam', 'Mataas Na Burol', 'Munting Ilog', 'Narra I', 'Narra II', 'Narra III', 'Paligawan', 'Pasong Langka', 'Pooc I', 'Pooc II', 'Pulong Bunga', 'Pulong Saging', 'Puting Kahoy', 'Sabutan', 'San Miguel I', 'San Miguel II', 'Santol', 'Tartaria', 'Tibig'],
                'Tanza' => ['Amaya I', 'Amaya II', 'Amaya III', 'Amaya IV', 'Amaya V', 'Amaya VI', 'Amaya VII', 'Bagtas', 'Biga', 'Biwas', 'Capipisa', 'Daang Amaya I', 'Daang Amaya II', 'Daang Amaya III', 'Halayhay', 'Julugan I', 'Julugan II', 'Julugan III', 'Julugan IV', 'Julugan V', 'Julugan VI', 'Julugan VII', 'Lambingan', 'Mulawin', 'Paradahan I', 'Paradahan II', 'Punta I', 'Punta II', 'Sahud Ulan', 'Sanja Mayor', 'Tres Cruses'],
                'Trece Martires' => ['Aguado', 'Cabezas', 'Conchu', 'De Ocampo', 'Gregorio', 'Inocencio', 'Lapidario', 'Luciano', 'Osorio', 'Perez', 'San Agustin'],
            ],
        ];
    }
}
