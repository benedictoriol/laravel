<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('owner_settings')) {
            Schema::create('owner_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->unique()->constrained('shops')->cascadeOnDelete();
                $table->string('shop_name')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('operating_hours')->nullable();
                $table->decimal('default_labor_rate', 10, 2)->default(0);
                $table->decimal('rush_fee_percent', 10, 2)->default(0);
                $table->decimal('default_profit_margin', 10, 2)->default(0);
                $table->unsignedInteger('minimum_order_quantity')->default(1);
                $table->unsignedInteger('max_rush_orders_per_day')->default(0);
                $table->text('cancellation_rules')->nullable();
                $table->json('notification_settings_json')->nullable();
                $table->json('delivery_defaults_json')->nullable();
                $table->json('ui_preferences_json')->nullable();
                $table->json('security_settings_json')->nullable();
                $table->json('workflow_automation_settings_json')->nullable();
                $table->json('document_settings_json')->nullable();
                $table->json('approval_settings_json')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->text('materials_supplied')->nullable();
                $table->unsignedInteger('lead_time_days')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('raw_materials')) {
            Schema::create('raw_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('material_name');
                $table->string('category')->nullable();
                $table->string('color')->nullable();
                $table->string('unit')->default('pcs');
                $table->decimal('stock_quantity', 12, 2)->default(0);
                $table->decimal('reorder_level', 12, 2)->default(0);
                $table->decimal('cost_per_unit', 12, 2)->default(0);
                $table->timestamp('last_restocked_at')->nullable();
                $table->string('status')->default('in_stock');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('supply_orders')) {
            Schema::create('supply_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->string('po_number')->unique();
                $table->json('materials_json')->nullable();
                $table->decimal('quantity_total', 12, 2)->default(0);
                $table->decimal('total_cost', 12, 2)->default(0);
                $table->date('ordered_at')->nullable();
                $table->date('expected_arrival_at')->nullable();
                $table->date('received_at')->nullable();
                $table->string('status')->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('quality_checks')) {
            Schema::create('quality_checks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('result');
                $table->text('issue_notes')->nullable();
                $table->json('attachments_json')->nullable();
                $table->boolean('rework_required')->default(false);
                $table->text('action_taken')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workforce_schedules')) {
            Schema::create('workforce_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('shift_date');
                $table->time('shift_start')->nullable();
                $table->time('shift_end')->nullable();
                $table->text('assignment_notes')->nullable();
                $table->boolean('is_day_off')->default(false);
                $table->boolean('is_overtime')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dispute_cases')) {
            Schema::create('dispute_cases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('complainant_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_handler_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('dispute_type');
                $table->text('issue_summary');
                $table->json('attachments_json')->nullable();
                $table->string('status')->default('open');
                $table->text('resolution')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('message_threads')) {
            Schema::create('message_threads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('type')->default('group');
                $table->string('title');
                $table->json('participant_user_ids_json')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
                $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->longText('message');
                $table->json('attachments_json')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('shop_services')) {
            Schema::table('shop_services', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_services', 'unit_price')) {
                    $table->decimal('unit_price', 10, 2)->default(0)->after('base_price');
                }
                if (!Schema::hasColumn('shop_services', 'stitch_range')) {
                    $table->string('stitch_range')->nullable()->after('unit_price');
                }
                if (!Schema::hasColumn('shop_services', 'complexity_multiplier')) {
                    $table->decimal('complexity_multiplier', 10, 2)->default(1)->after('stitch_range');
                }
                if (!Schema::hasColumn('shop_services', 'rush_fee_allowed')) {
                    $table->boolean('rush_fee_allowed')->default(true)->after('complexity_multiplier');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('dispute_cases');
        Schema::dropIfExists('workforce_schedules');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('supply_orders');
        Schema::dropIfExists('raw_materials');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('owner_settings');
    }
};
