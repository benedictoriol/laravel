<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('design_customizations')) {
            return;
        }

        Schema::table('design_customizations', function (Blueprint $table) {
            if (! Schema::hasColumn('design_customizations', 'design_session_json')) {
                $table->longText('design_session_json')->nullable()->after('pricing_breakdown_json');
            }
            if (! Schema::hasColumn('design_customizations', 'preview_meta_json')) {
                $table->longText('preview_meta_json')->nullable()->after('design_session_json');
            }
            if (! Schema::hasColumn('design_customizations', 'pricing_confidence_score')) {
                $table->decimal('pricing_confidence_score', 5, 2)->nullable()->after('preview_meta_json');
            }
            if (! Schema::hasColumn('design_customizations', 'pricing_strategy')) {
                $table->string('pricing_strategy', 80)->nullable()->after('pricing_confidence_score');
            }
            if (! Schema::hasColumn('design_customizations', 'last_priced_at')) {
                $table->dateTime('last_priced_at')->nullable()->after('pricing_strategy');
            }
            if (! Schema::hasColumn('design_customizations', 'approved_proof_id')) {
                $table->foreignId('approved_proof_id')->nullable()->after('last_priced_at')->constrained('design_proofs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('design_customizations')) {
            return;
        }

        Schema::table('design_customizations', function (Blueprint $table) {
            foreach (['design_session_json','preview_meta_json','pricing_confidence_score','pricing_strategy','last_priced_at'] as $column) {
                if (Schema::hasColumn('design_customizations', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('design_customizations', 'approved_proof_id')) {
                $table->dropConstrainedForeignId('approved_proof_id');
            }
        });
    }
};
