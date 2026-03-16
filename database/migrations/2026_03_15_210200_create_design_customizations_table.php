<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('design_customizations')) return;
        Schema::create('design_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_post_id')->nullable()->constrained('design_posts')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('garment_type', 100)->nullable();
            $table->string('placement_area', 100)->nullable();
            $table->string('fabric_type', 100)->nullable();
            $table->decimal('width_mm', 10, 2)->nullable();
            $table->decimal('height_mm', 10, 2)->nullable();
            $table->integer('color_count')->nullable();
            $table->integer('stitch_count_estimate')->nullable();
            $table->enum('complexity_level', ['simple', 'standard', 'complex', 'premium'])->default('standard');
            $table->longText('special_styles_json')->nullable();
            $table->text('notes')->nullable();
            $table->string('artwork_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->enum('status', ['draft', 'estimated', 'proof_ready', 'approved', 'archived'])->default('draft');
            $table->decimal('estimated_base_price', 12, 2)->default(0);
            $table->decimal('estimated_total_price', 12, 2)->default(0);
            $table->longText('pricing_breakdown_json')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('design_customizations'); }
};
