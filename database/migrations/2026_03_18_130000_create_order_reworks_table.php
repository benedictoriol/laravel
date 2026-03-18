<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_reworks')) {
            Schema::create('order_reworks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('quality_check_id')->nullable()->constrained('quality_checks')->nullOnDelete();
                $table->foreignId('design_customization_id')->nullable()->constrained('design_customizations')->nullOnDelete();
                $table->foreignId('production_package_id')->nullable()->constrained('design_production_packages')->nullOnDelete();
                $table->text('reason');
                $table->string('severity')->default('medium');
                $table->string('status')->default('rework_open');
                $table->text('internal_note')->nullable();
                $table->text('progress_notes')->nullable();
                $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('returned_to_qc_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_reworks');
    }
};
