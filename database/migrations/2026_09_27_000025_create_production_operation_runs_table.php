<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_operation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_stage_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_operation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status', 30)->default('pending');
            $table->decimal('planned_quantity', 18, 4)->nullable();
            $table->decimal('input_quantity', 18, 4)->nullable();
            $table->decimal('output_quantity', 18, 4)->nullable();
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['production_stage_run_id', 'sequence']);
            $table->index(['production_operation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_operation_runs');
    }
};