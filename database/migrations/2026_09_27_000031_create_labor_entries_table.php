<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('labor_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_stage_run_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('production_operation_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete(); $table->timestamp('started_at'); $table->timestamp('ended_at');
            $table->unsignedInteger('duration_minutes'); $table->decimal('hourly_rate',20,4); $table->decimal('total_cost',20,4); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['production_order_id','started_at']); $table->index(['employee_id','started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labor_entries');
    }
};
