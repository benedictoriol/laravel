<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('client_profiles', 'first_name')) $table->string('first_name', 100)->nullable()->after('postal_code');
            if (! Schema::hasColumn('client_profiles', 'middle_name')) $table->string('middle_name', 100)->nullable()->after('first_name');
            if (! Schema::hasColumn('client_profiles', 'last_name')) $table->string('last_name', 100)->nullable()->after('middle_name');
            if (! Schema::hasColumn('client_profiles', 'email')) $table->string('email')->nullable()->after('last_name');
            if (! Schema::hasColumn('client_profiles', 'phone')) $table->string('phone', 30)->nullable()->after('email');
            if (! Schema::hasColumn('client_profiles', 'billing_contact_name')) $table->string('billing_contact_name', 180)->nullable()->after('phone');
            if (! Schema::hasColumn('client_profiles', 'billing_phone')) $table->string('billing_phone', 30)->nullable()->after('billing_contact_name');
            if (! Schema::hasColumn('client_profiles', 'billing_email')) $table->string('billing_email')->nullable()->after('billing_phone');
            if (! Schema::hasColumn('client_profiles', 'preferred_payment_method_id')) $table->foreignId('preferred_payment_method_id')->nullable()->after('billing_email')->constrained('client_payment_methods')->nullOnDelete();
        });

        Schema::table('client_saved_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('client_saved_addresses', 'country_name')) $table->string('country_name', 80)->nullable()->after('postal_code');
            if (! Schema::hasColumn('client_saved_addresses', 'province_name')) $table->string('province_name', 80)->nullable()->after('country_name');
            if (! Schema::hasColumn('client_saved_addresses', 'city_name')) $table->string('city_name', 120)->nullable()->after('province_name');
            if (! Schema::hasColumn('client_saved_addresses', 'barangay_name')) $table->string('barangay_name', 120)->nullable()->after('city_name');
            if (! Schema::hasColumn('client_saved_addresses', 'house_number_street')) $table->string('house_number_street', 255)->nullable()->after('barangay_name');
            if (! Schema::hasColumn('client_saved_addresses', 'other_house_information')) $table->string('other_house_information', 255)->nullable()->after('house_number_street');
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('client_profiles', 'preferred_payment_method_id')) $table->dropConstrainedForeignId('preferred_payment_method_id');
            foreach (['billing_email','billing_phone','billing_contact_name','phone','email','last_name','middle_name','first_name'] as $col) {
                if (Schema::hasColumn('client_profiles', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('client_saved_addresses', function (Blueprint $table) {
            foreach (['other_house_information','house_number_street','barangay_name','city_name','province_name','country_name'] as $col) {
                if (Schema::hasColumn('client_saved_addresses', $col)) $table->dropColumn($col);
            }
        });
    }
};
