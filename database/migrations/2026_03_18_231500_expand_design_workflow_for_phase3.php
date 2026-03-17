<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('design_customizations')) {
            Schema::table('design_customizations', function (Blueprint $table) {
                if (! Schema::hasColumn('design_customizations', 'workflow_status')) {
                    $table->string('workflow_status', 50)->nullable()->after('status');
                }
                if (! Schema::hasColumn('design_customizations', 'current_version_no')) {
                    $table->unsignedInteger('current_version_no')->default(1)->after('workflow_status');
                }
                if (! Schema::hasColumn('design_customizations', 'approved_version_no')) {
                    $table->unsignedInteger('approved_version_no')->nullable()->after('current_version_no');
                }
                if (! Schema::hasColumn('design_customizations', 'submitted_at')) {
                    $table->dateTime('submitted_at')->nullable()->after('approved_version_no');
                }
                if (! Schema::hasColumn('design_customizations', 'last_revision_requested_at')) {
                    $table->dateTime('last_revision_requested_at')->nullable()->after('submitted_at');
                }
                if (! Schema::hasColumn('design_customizations', 'locked_at')) {
                    $table->dateTime('locked_at')->nullable()->after('last_revision_requested_at');
                }
            });
        }

        if (Schema::hasTable('design_proofs')) {
            Schema::table('design_proofs', function (Blueprint $table) {
                if (! Schema::hasColumn('design_proofs', 'version_no')) {
                    $table->unsignedInteger('version_no')->default(1)->after('proof_no');
                }
                if (! Schema::hasColumn('design_proofs', 'proof_summary_json')) {
                    $table->longText('proof_summary_json')->nullable()->after('pricing_snapshot_json');
                }
            });
        }

        if (! Schema::hasTable('design_workflow_events')) {
            Schema::create('design_workflow_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('design_customization_id')->constrained('design_customizations')->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 80);
                $table->string('summary', 255);
                $table->text('details')->nullable();
                $table->longText('event_meta_json')->nullable();
                $table->timestamps();
                $table->index(['design_customization_id', 'event_type'], 'idx_design_workflow_events_lookup');
            });
        }
    }

    public function down(): void
    {
        // compatibility no-op
    }
};
