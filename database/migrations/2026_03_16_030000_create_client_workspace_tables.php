<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_payment_methods')) {
            Schema::create('client_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('label');
                $table->string('method_type', 50);
                $table->string('account_name')->nullable();
                $table->string('account_number')->nullable();
                $table->string('provider')->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('subject');
                $table->string('category', 50)->default('support');
                $table->string('priority', 20)->default('medium');
                $table->string('status', 30)->default('open');
                $table->longText('message');
                $table->json('attachments_json')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shop_hiring_openings')) {
            Schema::create('shop_hiring_openings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
                $table->string('title');
                $table->string('department', 100)->nullable();
                $table->string('employment_type', 50)->nullable();
                $table->text('description')->nullable();
                $table->string('status', 30)->default('open');
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'category')) {
                $table->string('category', 50)->nullable()->after('type');
            }
            if (! Schema::hasColumn('notifications', 'priority')) {
                $table->string('priority', 20)->nullable()->after('category');
            }
            if (! Schema::hasColumn('notifications', 'action_label')) {
                $table->string('action_label')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            foreach (['action_label', 'priority', 'category'] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('shop_hiring_openings');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('client_payment_methods');
    }
};
