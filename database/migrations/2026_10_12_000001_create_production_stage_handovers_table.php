<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_stage_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_stage_run_id')->constrained('production_stage_runs')->cascadeOnDelete();
            $table->foreignId('to_stage_run_id')->constrained('production_stage_runs')->cascadeOnDelete();
            $table->decimal('sent_quantity', 18, 4);
            $table->decimal('received_quantity', 18, 4)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['from_stage_run_id', 'to_stage_run_id'], 'prod_stage_handover_pair_unique');
            $table->unique('to_stage_run_id', 'prod_stage_handover_to_unique');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stage_handovers');
    }
};
