<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (! Schema::hasColumn('suppliers', 'supplier_category_json')) {
                    $table->json('supplier_category_json')->nullable()->after('address');
                }
                if (! Schema::hasColumn('suppliers', 'preferred_supplier')) {
                    $table->boolean('preferred_supplier')->default(false)->after('supplier_category_json');
                }
                if (! Schema::hasColumn('suppliers', 'payment_terms')) {
                    $table->string('payment_terms')->nullable()->after('lead_time_days');
                }
                if (! Schema::hasColumn('suppliers', 'latest_unit_price')) {
                    $table->decimal('latest_unit_price', 12, 2)->nullable()->after('payment_terms');
                }
                if (! Schema::hasColumn('suppliers', 'previous_unit_price')) {
                    $table->decimal('previous_unit_price', 12, 2)->nullable()->after('latest_unit_price');
                }
                if (! Schema::hasColumn('suppliers', 'price_history_json')) {
                    $table->json('price_history_json')->nullable()->after('previous_unit_price');
                }
                if (! Schema::hasColumn('suppliers', 'minimum_order_quantity')) {
                    $table->decimal('minimum_order_quantity', 12, 2)->nullable()->after('price_history_json');
                }
                if (! Schema::hasColumn('suppliers', 'bulk_discount_levels_json')) {
                    $table->json('bulk_discount_levels_json')->nullable()->after('minimum_order_quantity');
                }
                if (! Schema::hasColumn('suppliers', 'last_price_updated_at')) {
                    $table->timestamp('last_price_updated_at')->nullable()->after('bulk_discount_levels_json');
                }
            });
        }

        if (Schema::hasTable('supply_orders')) {
            Schema::table('supply_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('supply_orders', 'actual_arrival_at')) {
                    $table->timestamp('actual_arrival_at')->nullable()->after('expected_arrival_at');
                }
                if (! Schema::hasColumn('supply_orders', 'missing_items_json')) {
                    $table->json('missing_items_json')->nullable()->after('actual_arrival_at');
                }
                if (! Schema::hasColumn('supply_orders', 'damaged_items_json')) {
                    $table->json('damaged_items_json')->nullable()->after('missing_items_json');
                }
                if (! Schema::hasColumn('supply_orders', 'delivery_notes')) {
                    $table->text('delivery_notes')->nullable()->after('damaged_items_json');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supply_orders')) {
            Schema::table('supply_orders', function (Blueprint $table) {
                foreach (['actual_arrival_at', 'missing_items_json', 'damaged_items_json', 'delivery_notes'] as $column) {
                    if (Schema::hasColumn('supply_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                foreach (['supplier_category_json', 'preferred_supplier', 'payment_terms', 'latest_unit_price', 'previous_unit_price', 'price_history_json', 'minimum_order_quantity', 'bulk_discount_levels_json', 'last_price_updated_at'] as $column) {
                    if (Schema::hasColumn('suppliers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
