<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('client_saved_addresses')) {
            return;
        }

        Schema::create('client_saved_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_profile_id')->constrained('client_profiles')->cascadeOnDelete();
            $table->string('label', 60)->default('Address');
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->foreignId('cavite_location_id')->nullable()->constrained('cavite_locations')->nullOnDelete();
            $table->text('address_line');
            $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('delivery_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_saved_addresses');
    }
};
