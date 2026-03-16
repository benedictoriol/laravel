<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bargaining_offers')) return;
        Schema::create('bargaining_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_post_id')->constrained('design_posts')->cascadeOnDelete();
            $table->foreignId('job_post_application_id')->nullable()->constrained('job_post_applications')->nullOnDelete();
            $table->foreignId('parent_offer_id')->nullable()->constrained('bargaining_offers')->nullOnDelete();
            $table->foreignId('offered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->integer('estimated_days')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'countered', 'withdrawn'])->default('pending');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bargaining_offers'); }
};
