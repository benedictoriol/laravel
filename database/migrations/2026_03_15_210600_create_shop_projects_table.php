<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('shop_projects')) return;
        Schema::create('shop_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description');
            $table->string('category', 80)->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->integer('min_order_qty')->default(1);
            $table->integer('turnaround_days')->nullable();
            $table->boolean('is_customizable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('preview_image_path')->nullable();
            $table->enum('default_fulfillment_type', ['pickup', 'delivery'])->default('pickup');
            $table->longText('automation_profile_json')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('shop_projects'); }
};
