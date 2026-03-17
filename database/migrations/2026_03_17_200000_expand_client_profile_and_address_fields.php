<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('client_profiles')) {
            Schema::table('client_profiles', function (Blueprint $table) {
                $columns = [
                    'first_name' => fn () => $table->string('first_name', 100)->nullable()->after('user_id'),
                    'middle_name' => fn () => $table->string('middle_name', 100)->nullable()->after('first_name'),
                    'last_name' => fn () => $table->string('last_name', 100)->nullable()->after('middle_name'),
                    'email' => fn () => $table->string('email')->nullable()->after('last_name'),
                    'phone_number' => fn () => $table->string('phone_number', 30)->nullable()->after('email'),
                    'registered_at_platform' => fn () => $table->timestamp('registered_at_platform')->nullable()->after('phone_number'),
                    'billing_contact_name' => fn () => $table->string('billing_contact_name', 150)->nullable()->after('registered_at_platform'),
                    'billing_phone' => fn () => $table->string('billing_phone', 30)->nullable()->after('billing_contact_name'),
                    'billing_email' => fn () => $table->string('billing_email')->nullable()->after('billing_phone'),
                ];
                foreach ($columns as $name => $callback) {
                    if (! Schema::hasColumn('client_profiles', $name)) {
                        $callback();
                    }
                }
            });
        }

        if (Schema::hasTable('client_saved_addresses')) {
            Schema::table('client_saved_addresses', function (Blueprint $table) {
                $columns = [
                    'country' => fn () => $table->string('country', 80)->nullable()->after('recipient_phone'),
                    'province' => fn () => $table->string('province', 80)->nullable()->after('country'),
                    'city_municipality' => fn () => $table->string('city_municipality', 120)->nullable()->after('province'),
                    'barangay' => fn () => $table->string('barangay', 120)->nullable()->after('city_municipality'),
                    'house_street' => fn () => $table->string('house_street', 255)->nullable()->after('barangay'),
                    'address_line_2' => fn () => $table->string('address_line_2', 255)->nullable()->after('house_street'),
                ];
                foreach ($columns as $name => $callback) {
                    if (! Schema::hasColumn('client_saved_addresses', $name)) {
                        $callback();
                    }
                }
            });
        }
    }

    public function down(): void
    {
    }
};
