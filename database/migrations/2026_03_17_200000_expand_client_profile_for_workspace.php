<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_profiles')) {
            Schema::table('client_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('client_profiles', 'first_name')) $table->string('first_name', 100)->nullable()->after('user_id');
                if (! Schema::hasColumn('client_profiles', 'middle_name')) $table->string('middle_name', 100)->nullable()->after('first_name');
                if (! Schema::hasColumn('client_profiles', 'last_name')) $table->string('last_name', 100)->nullable()->after('middle_name');
                if (! Schema::hasColumn('client_profiles', 'email')) $table->string('email', 180)->nullable()->after('last_name');
                if (! Schema::hasColumn('client_profiles', 'phone_number')) $table->string('phone_number', 30)->nullable()->after('email');
                if (! Schema::hasColumn('client_profiles', 'registration_date')) $table->date('registration_date')->nullable()->after('phone_number');
                if (! Schema::hasColumn('client_profiles', 'billing_contact_name')) $table->string('billing_contact_name', 180)->nullable()->after('registration_date');
                if (! Schema::hasColumn('client_profiles', 'billing_phone')) $table->string('billing_phone', 30)->nullable()->after('billing_contact_name');
                if (! Schema::hasColumn('client_profiles', 'billing_email')) $table->string('billing_email', 180)->nullable()->after('billing_phone');
                if (! Schema::hasColumn('client_profiles', 'default_payment_method')) $table->string('default_payment_method', 100)->nullable()->after('billing_email');
            });
        }

        if (Schema::hasTable('client_saved_addresses')) {
            Schema::table('client_saved_addresses', function (Blueprint $table) {
                if (! Schema::hasColumn('client_saved_addresses', 'country')) $table->string('country', 100)->nullable()->after('recipient_phone');
                if (! Schema::hasColumn('client_saved_addresses', 'province')) $table->string('province', 100)->nullable()->after('country');
                if (! Schema::hasColumn('client_saved_addresses', 'city_municipality')) $table->string('city_municipality', 120)->nullable()->after('province');
                if (! Schema::hasColumn('client_saved_addresses', 'barangay')) $table->string('barangay', 120)->nullable()->after('city_municipality');
                if (! Schema::hasColumn('client_saved_addresses', 'house_street')) $table->string('house_street')->nullable()->after('barangay');
                if (! Schema::hasColumn('client_saved_addresses', 'other_house_information')) $table->string('other_house_information')->nullable()->after('house_street');
            });
        }
    }

    public function down(): void
    {
    }
};
