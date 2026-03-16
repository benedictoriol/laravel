<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('design_proofs')) {
            Schema::table('design_proofs', function (Blueprint $table) {
                if (! Schema::hasColumn('design_proofs', 'expires_at')) {
                    $table->dateTime('expires_at')->nullable()->after('responded_at');
                }
            });
        }

        if (Schema::hasTable('bargaining_offers')) {
            Schema::table('bargaining_offers', function (Blueprint $table) {
                if (! Schema::hasColumn('bargaining_offers', 'expires_at')) {
                    $table->dateTime('expires_at')->nullable()->after('responded_at');
                }
                if (! Schema::hasColumn('bargaining_offers', 'negotiation_round')) {
                    $table->unsignedInteger('negotiation_round')->default(1)->after('expires_at');
                }
            });
        }

        if (Schema::hasTable('shop_projects')) {
            Schema::table('shop_projects', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_projects', 'tags_json')) {
                    $table->longText('tags_json')->nullable()->after('automation_profile_json');
                }
            });
        }
    }

    public function down(): void
    {
        // no-op rollback for compatibility with mixed schemas
    }
};
