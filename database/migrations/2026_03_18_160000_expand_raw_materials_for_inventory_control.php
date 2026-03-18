<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('raw_materials', 'material_code')) {
                $table->string('material_code')->nullable()->after('material_name');
            }
            if (! Schema::hasColumn('raw_materials', 'sku')) {
                $table->string('sku')->nullable()->after('material_code');
            }
            if (! Schema::hasColumn('raw_materials', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (! Schema::hasColumn('raw_materials', 'minimum_stock_level')) {
                $table->decimal('minimum_stock_level', 12, 2)->default(0)->after('stock_quantity');
            }
            if (! Schema::hasColumn('raw_materials', 'reorder_threshold')) {
                $table->decimal('reorder_threshold', 12, 2)->default(0)->after('minimum_stock_level');
            }
            if (! Schema::hasColumn('raw_materials', 'maximum_stock_capacity')) {
                $table->decimal('maximum_stock_capacity', 12, 2)->nullable()->after('reorder_threshold');
            }
            if (! Schema::hasColumn('raw_materials', 'supplier_name')) {
                $table->string('supplier_name')->nullable()->after('supplier_id');
            }
            if (! Schema::hasColumn('raw_materials', 'supplier_code')) {
                $table->string('supplier_code')->nullable()->after('supplier_name');
            }
            if (! Schema::hasColumn('raw_materials', 'preferred_supplier')) {
                $table->boolean('preferred_supplier')->default(false)->after('supplier_code');
            }
            if (! Schema::hasColumn('raw_materials', 'unit_purchase_cost')) {
                $table->decimal('unit_purchase_cost', 12, 2)->default(0)->after('cost_per_unit');
            }
            if (! Schema::hasColumn('raw_materials', 'latest_cost')) {
                $table->decimal('latest_cost', 12, 2)->default(0)->after('unit_purchase_cost');
            }
            if (! Schema::hasColumn('raw_materials', 'average_cost')) {
                $table->decimal('average_cost', 12, 2)->default(0)->after('latest_cost');
            }
            if (! Schema::hasColumn('raw_materials', 'selling_cost_contribution')) {
                $table->decimal('selling_cost_contribution', 12, 2)->default(0)->after('average_cost');
            }
            if (! Schema::hasColumn('raw_materials', 'estimated_usage_per_order_unit')) {
                $table->decimal('estimated_usage_per_order_unit', 12, 4)->default(0)->after('selling_cost_contribution');
            }
            if (! Schema::hasColumn('raw_materials', 'usage_measurement')) {
                $table->string('usage_measurement')->nullable()->after('estimated_usage_per_order_unit');
            }
            if (! Schema::hasColumn('raw_materials', 'thread_color')) {
                $table->string('thread_color')->nullable()->after('usage_measurement');
            }
            if (! Schema::hasColumn('raw_materials', 'thread_type')) {
                $table->string('thread_type')->nullable()->after('thread_color');
            }
            if (! Schema::hasColumn('raw_materials', 'brand')) {
                $table->string('brand')->nullable()->after('thread_type');
            }
            if (! Schema::hasColumn('raw_materials', 'thickness')) {
                $table->string('thickness')->nullable()->after('brand');
            }
            if (! Schema::hasColumn('raw_materials', 'fabric_type')) {
                $table->string('fabric_type')->nullable()->after('thickness');
            }
            if (! Schema::hasColumn('raw_materials', 'fabric_color')) {
                $table->string('fabric_color')->nullable()->after('fabric_type');
            }
            if (! Schema::hasColumn('raw_materials', 'texture')) {
                $table->string('texture')->nullable()->after('fabric_color');
            }
            if (! Schema::hasColumn('raw_materials', 'backing_type')) {
                $table->string('backing_type')->nullable()->after('texture');
            }
            if (! Schema::hasColumn('raw_materials', 'weight')) {
                $table->string('weight')->nullable()->after('backing_type');
            }
            if (! Schema::hasColumn('raw_materials', 'stock_status')) {
                $table->string('stock_status')->default('in_stock')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $columns = [
                'material_code', 'sku', 'description', 'minimum_stock_level', 'reorder_threshold', 'maximum_stock_capacity',
                'supplier_name', 'supplier_code', 'preferred_supplier', 'unit_purchase_cost', 'latest_cost', 'average_cost',
                'selling_cost_contribution', 'estimated_usage_per_order_unit', 'usage_measurement', 'thread_color',
                'thread_type', 'brand', 'thickness', 'fabric_type', 'fabric_color', 'texture', 'backing_type', 'weight', 'stock_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('raw_materials', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
