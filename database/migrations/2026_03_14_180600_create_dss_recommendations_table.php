<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dss_recommendations')) {
            return;
        }

        Schema::create('dss_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->enum('generated_for_type', ['client', 'admin', 'owner'])->default('client');
            $table->string('basis', 100);
            $table->decimal('score', 8, 4)->default(0);
            $table->integer('rank_position')->default(1);
            $table->longText('context_json')->nullable();
            $table->dateTime('generated_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_recommendations');
    }
};
