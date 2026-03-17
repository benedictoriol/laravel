<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('owner_pricing_rules')) {
            Schema::create('owner_pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->string('rule_type', 60);
                $table->string('rule_key', 120);
                $table->string('label', 180)->nullable();
                $table->json('config_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['shop_id', 'rule_type', 'rule_key'], 'owner_pricing_rules_shop_type_key_unique');
            });
        }

        if (!Schema::hasTable('shop_couriers')) {
            Schema::create('shop_couriers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('shop_services')) {
            Schema::table('shop_services', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_services', 'rush_multiplier')) {
                    $table->decimal('rush_multiplier', 8, 2)->default(1)->after('rush_fee_allowed');
                }
            });
        }

        if (Schema::hasTable('shop_projects')) {
            Schema::table('shop_projects', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_projects', 'embroidery_size')) {
                    $table->string('embroidery_size', 120)->nullable()->after('description');
                }
                if (!Schema::hasColumn('shop_projects', 'canvas_used')) {
                    $table->string('canvas_used', 120)->nullable()->after('embroidery_size');
                }
                if (!Schema::hasColumn('shop_projects', 'image_path')) {
                    $table->string('image_path')->nullable()->after('preview_image_path');
                }
            });
        }

        if (Schema::hasTable('shop_members')) {
            Schema::table('shop_members', function (Blueprint $table) {
                if (!Schema::hasColumn('shop_members', 'position')) {
                    $table->string('position', 120)->nullable()->after('member_role');
                }
                if (!Schema::hasColumn('shop_members', 'approval_status')) {
                    $table->string('approval_status', 40)->default('approved')->after('position');
                }
                if (!Schema::hasColumn('shop_members', 'hired_by_user_id')) {
                    $table->foreignId('hired_by_user_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('shop_members', 'reviewed_by_user_id')) {
                    $table->foreignId('reviewed_by_user_id')->nullable()->after('hired_by_user_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('shop_members', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
                }
            });
        }

        if (Schema::hasTable('workforce_schedules')) {
            Schema::table('workforce_schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('workforce_schedules', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->nullOnDelete();
                }
                if (!Schema::hasColumn('workforce_schedules', 'deadline_at')) {
                    $table->timestamp('deadline_at')->nullable()->after('shift_end');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_couriers');
        Schema::dropIfExists('owner_pricing_rules');
    }
};
