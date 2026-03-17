<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('design_customizations')) {
            Schema::table('design_customizations', function (Blueprint $table) {
                if (! Schema::hasColumn('design_customizations', 'digitizing_status')) {
                    $table->string('digitizing_status')->nullable()->after('production_status');
                }
                if (! Schema::hasColumn('design_customizations', 'machine_file_status')) {
                    $table->string('machine_file_status')->nullable()->after('digitizing_status');
                }
                if (! Schema::hasColumn('design_customizations', 'latest_digitizing_job_id')) {
                    $table->unsignedBigInteger('latest_digitizing_job_id')->nullable()->after('latest_production_package_id');
                }
                if (! Schema::hasColumn('design_customizations', 'digitizing_required_at')) {
                    $table->timestamp('digitizing_required_at')->nullable()->after('production_ready_at');
                }
                if (! Schema::hasColumn('design_customizations', 'machine_ready_at')) {
                    $table->timestamp('machine_ready_at')->nullable()->after('digitizing_required_at');
                }
                if (! Schema::hasColumn('design_customizations', 'digitizing_meta_json')) {
                    $table->json('digitizing_meta_json')->nullable()->after('production_meta_json');
                }
            });
        }

        if (! Schema::hasTable('design_digitizing_jobs')) {
            Schema::create('design_digitizing_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('design_customization_id');
                $table->unsignedBigInteger('design_proof_id')->nullable();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('assigned_digitizer_user_id')->nullable();
                $table->string('status')->default('pending_digitizing');
                $table->text('digitizing_notes')->nullable();
                $table->string('machine_file_status')->nullable();
                $table->unsignedInteger('revision_count')->default(0);
                $table->string('approval_state')->default('pending');
                $table->json('result_meta_json')->nullable();
                $table->timestamp('submitted_for_review_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('design_machine_files')) {
            Schema::create('design_machine_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('design_digitizing_job_id');
                $table->unsignedBigInteger('design_customization_id');
                $table->unsignedInteger('design_version_no')->default(1);
                $table->unsignedInteger('file_version')->default(1);
                $table->string('file_type', 20);
                $table->string('file_name')->nullable();
                $table->string('file_path')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('approval_state')->default('pending_review');
                $table->json('file_meta_json')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('design_machine_files')) {
            Schema::dropIfExists('design_machine_files');
        }
        if (Schema::hasTable('design_digitizing_jobs')) {
            Schema::dropIfExists('design_digitizing_jobs');
        }
        if (Schema::hasTable('design_customizations')) {
            Schema::table('design_customizations', function (Blueprint $table) {
                foreach (['digitizing_status','machine_file_status','latest_digitizing_job_id','digitizing_required_at','machine_ready_at','digitizing_meta_json'] as $column) {
                    if (Schema::hasColumn('design_customizations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
