<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dss_shop_metrics')) {
            return;
        }

        Schema::create('dss_shop_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('avg_rating', 4, 2)->nullable();
            $table->integer('review_count')->default(0);
            $table->decimal('completion_rate', 6, 4)->nullable();
            $table->decimal('avg_turnaround_days', 8, 2)->nullable();
            $table->integer('active_staff_count')->default(0);
            $table->integer('open_job_posts_taken')->default(0);
            $table->decimal('revenue_total', 14, 2)->default(0);
            $table->decimal('price_competitiveness_score', 6, 2)->nullable();
            $table->decimal('recommendation_score', 6, 2)->nullable();
            $table->decimal('delay_risk_score', 6, 2)->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_shop_metrics');
    }
};
