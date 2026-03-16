<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('client_profiles', 'organization_name')) $table->string('organization_name', 180)->nullable()->after('postal_code');
            if (! Schema::hasColumn('client_profiles', 'preferred_fulfillment_type')) $table->enum('preferred_fulfillment_type', ['pickup', 'delivery'])->nullable()->after('preferred_contact_method');
            if (! Schema::hasColumn('client_profiles', 'saved_measurements_json')) $table->longText('saved_measurements_json')->nullable()->after('preferred_fulfillment_type');
            if (! Schema::hasColumn('client_profiles', 'default_garment_preferences_json')) $table->longText('default_garment_preferences_json')->nullable()->after('saved_measurements_json');
            if (! Schema::hasColumn('client_profiles', 'notes')) $table->text('notes')->nullable()->after('default_garment_preferences_json');
        });
    }
    public function down(): void {}
};
