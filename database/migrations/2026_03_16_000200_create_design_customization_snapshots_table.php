<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('design_customization_snapshots')) {
            return;
        }

        Schema::create('design_customization_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_customization_id')->constrained('design_customizations')->cascadeOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->foreignId('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary', 180)->nullable();
            $table->longText('snapshot_json')->nullable();
            $table->longText('pricing_snapshot_json')->nullable();
            $table->timestamps();
            $table->index(['design_customization_id', 'version_no'], 'idx_design_customization_snapshots_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_customization_snapshots');
    }
};
