<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_customizations', function (Blueprint $table) {
            if (! Schema::hasColumn('design_customizations', 'production_status')) {
                $table->string('production_status', 60)->nullable()->after('locked_at');
            }
            if (! Schema::hasColumn('design_customizations', 'production_ready_at')) {
                $table->timestamp('production_ready_at')->nullable()->after('production_status');
            }
            if (! Schema::hasColumn('design_customizations', 'latest_production_package_id')) {
                $table->unsignedBigInteger('latest_production_package_id')->nullable()->after('production_ready_at');
            }
            if (! Schema::hasColumn('design_customizations', 'color_mapping_json')) {
                $table->json('color_mapping_json')->nullable()->after('latest_production_package_id');
            }
            if (! Schema::hasColumn('design_customizations', 'risk_flags_json')) {
                $table->json('risk_flags_json')->nullable()->after('color_mapping_json');
            }
            if (! Schema::hasColumn('design_customizations', 'suggested_quote_basis_json')) {
                $table->json('suggested_quote_basis_json')->nullable()->after('risk_flags_json');
            }
            if (! Schema::hasColumn('design_customizations', 'production_meta_json')) {
                $table->json('production_meta_json')->nullable()->after('suggested_quote_basis_json');
            }
        });

        if (! Schema::hasTable('design_production_packages')) {
            Schema::create('design_production_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('design_customization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('version_no')->default(1);
                $table->unsignedInteger('package_no')->default(1);
                $table->string('status', 50)->default('prepared');
                $table->string('preview_path')->nullable();
                $table->json('proof_summary_json')->nullable();
                $table->json('design_metadata_json')->nullable();
                $table->json('quote_basis_json')->nullable();
                $table->json('thread_mapping_json')->nullable();
                $table->json('risk_flags_json')->nullable();
                $table->json('production_summary_json')->nullable();
                $table->text('internal_note')->nullable();
                $table->text('qc_note')->nullable();
                $table->timestamp('handed_off_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('design_production_packages')) {
            Schema::dropIfExists('design_production_packages');
        }

        Schema::table('design_customizations', function (Blueprint $table) {
            foreach (['production_status','production_ready_at','latest_production_package_id','color_mapping_json','risk_flags_json','suggested_quote_basis_json','production_meta_json'] as $column) {
                if (Schema::hasColumn('design_customizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
