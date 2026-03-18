<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_customization_id')->nullable()->constrained('design_customizations')->nullOnDelete();
            $table->foreignId('production_package_id')->nullable()->constrained('design_production_packages')->nullOnDelete();
            $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->nullOnDelete();
            $table->string('material_category', 50);
            $table->string('material_key', 120);
            $table->string('material_name_snapshot', 150)->nullable();
            $table->string('color_label', 120)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('estimate_quantity', 12, 4)->default(0);
            $table->decimal('deducted_quantity', 12, 4)->default(0);
            $table->decimal('actual_quantity', 12, 4)->nullable();
            $table->string('status', 30)->default('suggested');
            $table->json('source_meta_json')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'design_customization_id', 'production_package_id', 'material_key'], 'material_consumptions_unique_context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
    }
};
