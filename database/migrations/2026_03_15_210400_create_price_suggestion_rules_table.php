<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('price_suggestion_rules')) {
            Schema::create('price_suggestion_rules', function (Blueprint $table) {
                $table->id();
                $table->string('rule_code', 80)->unique();
                $table->string('rule_name', 180);
                $table->string('category', 80)->default('general');
                $table->enum('amount_type', ['fixed', 'percent'])->default('fixed');
                $table->decimal('amount_value', 12, 2)->default(0);
                $table->longText('conditions_json')->nullable();
                $table->integer('priority')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (DB::table('price_suggestion_rules')->count() === 0) {
            DB::table('price_suggestion_rules')->insert([
                ['rule_code' => 'RUSH_MARKUP', 'rule_name' => 'Rush markup baseline', 'category' => 'speed', 'amount_type' => 'percent', 'amount_value' => 15, 'conditions_json' => json_encode(['design_type' => null]), 'priority' => 5, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['rule_code' => 'PREMIUM_COMPLEXITY', 'rule_name' => 'Premium complexity markup', 'category' => 'complexity', 'amount_type' => 'percent', 'amount_value' => 12, 'conditions_json' => json_encode(['complexity_level' => 'premium']), 'priority' => 10, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['rule_code' => 'BULK_PREP', 'rule_name' => 'Bulk preparation fee', 'category' => 'bulk', 'amount_type' => 'fixed', 'amount_value' => 120, 'conditions_json' => json_encode(['minimum_quantity' => 50]), 'priority' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
    public function down(): void { Schema::dropIfExists('price_suggestion_rules'); }
};
