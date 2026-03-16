<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('design_proofs')) return;
        Schema::create('design_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_customization_id')->constrained('design_customizations')->cascadeOnDelete();
            $table->unsignedInteger('proof_no')->default(1);
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('preview_file_path');
            $table->text('annotated_notes')->nullable();
            $table->longText('pricing_snapshot_json')->nullable();
            $table->enum('status', ['pending_client', 'approved', 'rejected', 'superseded'])->default('pending_client');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('design_proofs'); }
};
