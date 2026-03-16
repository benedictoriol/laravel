<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_assignments')) {
            return;
        }

        Schema::create('order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->enum('assignment_role', ['hr', 'staff']);
            $table->enum('assignment_type', ['digitizing', 'embroidery', 'quality_check', 'packing', 'delivery', 'other'])->default('embroidery');
            $table->enum('status', ['assigned', 'in_progress', 'done', 'cancelled'])->default('assigned');
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['assigned_to']);
            $table->index(['assignment_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_assignments');
    }
};
