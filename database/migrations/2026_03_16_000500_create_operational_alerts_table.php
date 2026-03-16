<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('operational_alerts')) {
            return;
        }

        Schema::create('operational_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 80);
            $table->enum('severity', ['low','medium','high','critical'])->default('medium');
            $table->string('title', 180);
            $table->text('message');
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['open','resolved','dismissed'])->default('open');
            $table->dateTime('resolved_at')->nullable();
            $table->longText('meta_json')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'status'], 'idx_operational_alerts_shop_status');
            $table->index(['reference_type', 'reference_id'], 'idx_operational_alerts_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_alerts');
    }
};
