<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('quality_checks', 'design_customization_id')) {
                $table->foreignId('design_customization_id')->nullable()->after('order_id')->constrained('design_customizations')->nullOnDelete();
            }
            if (! Schema::hasColumn('quality_checks', 'design_proof_id')) {
                $table->foreignId('design_proof_id')->nullable()->after('design_customization_id')->constrained('design_proofs')->nullOnDelete();
            }
            if (! Schema::hasColumn('quality_checks', 'production_package_id')) {
                $table->foreignId('production_package_id')->nullable()->after('design_proof_id')->constrained('design_production_packages')->nullOnDelete();
            }
            if (! Schema::hasColumn('quality_checks', 'qc_status')) {
                $table->string('qc_status')->default('pending_qc')->after('checked_by');
            }
            if (! Schema::hasColumn('quality_checks', 'qc_notes')) {
                $table->text('qc_notes')->nullable()->after('qc_status');
            }
            if (! Schema::hasColumn('quality_checks', 'defect_type')) {
                $table->string('defect_type')->nullable()->after('qc_notes');
            }
            if (! Schema::hasColumn('quality_checks', 'defect_notes')) {
                $table->text('defect_notes')->nullable()->after('defect_type');
            }
            if (! Schema::hasColumn('quality_checks', 'qc_risk')) {
                $table->string('qc_risk')->nullable()->after('defect_notes');
            }
            if (! Schema::hasColumn('quality_checks', 'remarks')) {
                $table->text('remarks')->nullable()->after('qc_risk');
            }
            if (! Schema::hasColumn('quality_checks', 'checks_json')) {
                $table->json('checks_json')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('quality_checks', 'evidence_json')) {
                $table->json('evidence_json')->nullable()->after('checks_json');
            }
            if (! Schema::hasColumn('quality_checks', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('checked_at');
            }
            if (! Schema::hasColumn('quality_checks', 'passed_at')) {
                $table->timestamp('passed_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('quality_checks', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('passed_at');
            }
            if (! Schema::hasColumn('quality_checks', 'rework_opened_at')) {
                $table->timestamp('rework_opened_at')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quality_checks', function (Blueprint $table) {
            foreach ([
                'production_package_id',
                'design_proof_id',
                'design_customization_id',
            ] as $column) {
                if (Schema::hasColumn('quality_checks', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach ([
                'qc_status',
                'qc_notes',
                'defect_type',
                'defect_notes',
                'qc_risk',
                'remarks',
                'checks_json',
                'evidence_json',
                'started_at',
                'passed_at',
                'failed_at',
                'rework_opened_at',
            ] as $column) {
                if (Schema::hasColumn('quality_checks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
