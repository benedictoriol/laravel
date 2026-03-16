<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fulfillments')) {
            return;
        }

        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('fulfillment_type', ['pickup', 'delivery']);
            $table->string('receiver_name', 150)->nullable();
            $table->string('receiver_contact', 50)->nullable();
            $table->foreignId('cavite_location_id')->nullable()->constrained('cavite_locations')->nullOnDelete();
            $table->text('delivery_address')->nullable();
            $table->string('courier_name', 120)->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->dateTime('pickup_schedule_at')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->enum('status', ['pending', 'scheduled', 'ready', 'shipped', 'delivered', 'picked_up', 'failed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
