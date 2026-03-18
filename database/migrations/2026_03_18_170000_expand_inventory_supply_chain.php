<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_materials', 'material_code')) $table->string('material_code')->nullable()->after('material_name');
            if (! Schema::hasColumn('raw_materials', 'sku')) $table->string('sku')->nullable()->after('material_code');
            if (! Schema::hasColumn('raw_materials', 'description')) $table->text('description')->nullable()->after('category');
            if (! Schema::hasColumn('raw_materials', 'reserved_quantity')) $table->decimal('reserved_quantity', 12, 2)->default(0)->after('stock_quantity');
            if (! Schema::hasColumn('raw_materials', 'minimum_stock_level')) $table->decimal('minimum_stock_level', 12, 2)->default(0)->after('reserved_quantity');
            if (! Schema::hasColumn('raw_materials', 'reorder_threshold')) $table->decimal('reorder_threshold', 12, 2)->default(0)->after('reorder_level');
            if (! Schema::hasColumn('raw_materials', 'maximum_stock_capacity')) $table->decimal('maximum_stock_capacity', 12, 2)->default(0)->after('reorder_threshold');
            if (! Schema::hasColumn('raw_materials', 'unit_purchase_cost')) $table->decimal('unit_purchase_cost', 12, 2)->default(0)->after('cost_per_unit');
            if (! Schema::hasColumn('raw_materials', 'latest_cost')) $table->decimal('latest_cost', 12, 2)->default(0)->after('unit_purchase_cost');
            if (! Schema::hasColumn('raw_materials', 'average_cost')) $table->decimal('average_cost', 12, 2)->default(0)->after('latest_cost');
            if (! Schema::hasColumn('raw_materials', 'selling_cost_contribution')) $table->decimal('selling_cost_contribution', 12, 2)->default(0)->after('average_cost');
            if (! Schema::hasColumn('raw_materials', 'estimated_usage_per_order_unit')) $table->decimal('estimated_usage_per_order_unit', 12, 4)->default(0)->after('selling_cost_contribution');
            if (! Schema::hasColumn('raw_materials', 'usage_measurement')) $table->string('usage_measurement')->nullable()->after('estimated_usage_per_order_unit');
            if (! Schema::hasColumn('raw_materials', 'supplier_name')) $table->string('supplier_name')->nullable()->after('supplier_id');
            if (! Schema::hasColumn('raw_materials', 'supplier_code')) $table->string('supplier_code')->nullable()->after('supplier_name');
            if (! Schema::hasColumn('raw_materials', 'preferred_supplier')) $table->boolean('preferred_supplier')->default(false)->after('supplier_code');
            if (! Schema::hasColumn('raw_materials', 'thread_color')) $table->string('thread_color')->nullable()->after('notes');
            if (! Schema::hasColumn('raw_materials', 'thread_type')) $table->string('thread_type')->nullable()->after('thread_color');
            if (! Schema::hasColumn('raw_materials', 'brand')) $table->string('brand')->nullable()->after('thread_type');
            if (! Schema::hasColumn('raw_materials', 'thickness')) $table->string('thickness')->nullable()->after('brand');
            if (! Schema::hasColumn('raw_materials', 'fabric_type')) $table->string('fabric_type')->nullable()->after('thickness');
            if (! Schema::hasColumn('raw_materials', 'fabric_color')) $table->string('fabric_color')->nullable()->after('fabric_type');
            if (! Schema::hasColumn('raw_materials', 'texture')) $table->string('texture')->nullable()->after('fabric_color');
            if (! Schema::hasColumn('raw_materials', 'backing_type')) $table->string('backing_type')->nullable()->after('texture');
            if (! Schema::hasColumn('raw_materials', 'weight')) $table->string('weight')->nullable()->after('backing_type');
            if (! Schema::hasColumn('raw_materials', 'stock_status')) $table->string('stock_status')->default('in_stock')->after('status');
        });

        if (! Schema::hasTable('material_consumptions')) {
            Schema::create('material_consumptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('design_customization_id')->nullable()->constrained('design_customizations')->nullOnDelete();
                $table->foreignId('production_package_id')->nullable()->constrained('design_production_packages')->nullOnDelete();
                $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->nullOnDelete();
                $table->string('material_name_snapshot');
                $table->string('material_code_snapshot')->nullable();
                $table->string('material_category')->nullable();
                $table->string('usage_type')->default('reserved');
                $table->string('unit')->default('pcs');
                $table->decimal('estimated_quantity', 12, 4)->default(0);
                $table->decimal('reserved_quantity', 12, 4)->default(0);
                $table->decimal('consumed_quantity', 12, 4)->default(0);
                $table->decimal('remaining_available_stock', 12, 4)->default(0);
                $table->string('status')->default('reserved');
                $table->json('meta_json')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('material_movements')) {
            Schema::create('material_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->nullOnDelete();
                $table->foreignId('material_consumption_id')->nullable()->constrained('material_consumptions')->nullOnDelete();
                $table->string('source');
                $table->string('destination');
                $table->decimal('quantity', 12, 4)->default(0);
                $table->timestamp('movement_date')->nullable();
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('supply_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('supply_orders', 'quantity_received')) $table->decimal('quantity_received', 12, 2)->default(0)->after('quantity_total');
            if (! Schema::hasColumn('supply_orders', 'delivery_status')) $table->string('delivery_status')->default('pending')->after('status');
            if (! Schema::hasColumn('supply_orders', 'actual_arrival_at')) $table->date('actual_arrival_at')->nullable()->after('expected_arrival_at');
        });
    }

    public function down(): void
    {
        // no-op safe rollback for additive migration
    }
};
