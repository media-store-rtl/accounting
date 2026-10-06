<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_stage_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_stage_run_id')->nullable()->constrained('production_stage_runs')->nullOnDelete();
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
            $table->index(['production_id', 'sequence']);
            $table->index(['production_stage_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stage_runs');
    }
};