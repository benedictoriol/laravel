<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('owner_settings')) {
            Schema::table('owner_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('owner_settings', 'pricing_rules_json')) {
                    $table->json('pricing_rules_json')->nullable()->after('approval_settings_json');
                }
                if (! Schema::hasColumn('owner_settings', 'quote_automation_controls_json')) {
                    $table->json('quote_automation_controls_json')->nullable()->after('pricing_rules_json');
                }
                if (! Schema::hasColumn('owner_settings', 'minimum_billable_amount')) {
                    $table->decimal('minimum_billable_amount', 12, 2)->default(0)->after('minimum_order_quantity');
                }
                if (! Schema::hasColumn('owner_settings', 'max_manual_discount_percent')) {
                    $table->decimal('max_manual_discount_percent', 8, 2)->default(0)->after('minimum_billable_amount');
                }
            });
        }


        if (Schema::hasTable('shop_services')) {
            Schema::table('shop_services', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_services', 'rush_multiplier')) {
                    $table->decimal('rush_multiplier', 10, 2)->default(1.15)->after('rush_fee_allowed');
                }
            });
        }

        if (Schema::hasTable('shop_projects')) {
            Schema::table('shop_projects', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_projects', 'embroidery_size')) {
                    $table->string('embroidery_size', 100)->nullable()->after('description');
                }
                if (! Schema::hasColumn('shop_projects', 'canvas_used')) {
                    $table->string('canvas_used', 120)->nullable()->after('embroidery_size');
                }
            });
        }

        if (Schema::hasTable('workforce_schedules')) {
            Schema::table('workforce_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('workforce_schedules', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->nullOnDelete();
                }
                if (! Schema::hasColumn('workforce_schedules', 'deadline_at')) {
                    $table->timestamp('deadline_at')->nullable()->after('shift_end');
                }
            });
        }

        if (Schema::hasTable('shop_members')) {
            Schema::table('shop_members', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_members', 'position')) {
                    $table->string('position', 120)->nullable()->after('member_role');
                }
                if (! Schema::hasColumn('shop_members', 'approval_status')) {
                    $table->string('approval_status', 40)->default('approved')->after('position');
                }
                if (! Schema::hasColumn('shop_members', 'review_notes')) {
                    $table->text('review_notes')->nullable()->after('approval_status');
                }
                if (! Schema::hasColumn('shop_members', 'created_by_user_id')) {
                    $table->foreignId('created_by_user_id')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('shop_members', 'reviewed_by_user_id')) {
                    $table->foreignId('reviewed_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('shop_members', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
                }
            });
        }

        if (! Schema::hasTable('shop_couriers')) {
            Schema::create('shop_couriers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('service_type', 50)->default('delivery');
                $table->string('coverage_area')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('owner_settings')) {
            Schema::table('owner_settings', function (Blueprint $table) {
                foreach (['pricing_rules_json', 'quote_automation_controls_json', 'minimum_billable_amount', 'max_manual_discount_percent'] as $column) {
                    if (Schema::hasColumn('owner_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }


        if (Schema::hasTable('shop_services')) {
            Schema::table('shop_services', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_services', 'rush_multiplier')) {
                    $table->decimal('rush_multiplier', 10, 2)->default(1.15)->after('rush_fee_allowed');
                }
            });
        }

        if (Schema::hasTable('shop_projects')) {
            Schema::table('shop_projects', function (Blueprint $table) {
                foreach (['embroidery_size', 'canvas_used'] as $column) {
                    if (Schema::hasColumn('shop_projects', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('workforce_schedules')) {
            Schema::table('workforce_schedules', function (Blueprint $table) {
                if (Schema::hasColumn('workforce_schedules', 'order_id')) {
                    $table->dropConstrainedForeignId('order_id');
                }
                if (Schema::hasColumn('workforce_schedules', 'deadline_at')) {
                    $table->dropColumn('deadline_at');
                }
            });
        }

        if (Schema::hasTable('shop_members')) {
            Schema::table('shop_members', function (Blueprint $table) {
                foreach (['reviewed_by_user_id', 'created_by_user_id'] as $column) {
                    if (Schema::hasColumn('shop_members', $column)) {
                        $table->dropConstrainedForeignId($column);
                    }
                }
                foreach (['position', 'approval_status', 'review_notes', 'reviewed_at'] as $column) {
                    if (Schema::hasColumn('shop_members', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('shop_couriers');
    }
};
