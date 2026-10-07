<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnel_labor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained()->restrictOnDelete();
            $table->string('rate_type', 20);
            $table->decimal('rate', 20, 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'personnel_id', 'effective_from']);
        });

        Schema::create('production_labor_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_id')->constrained('productions')->restrictOnDelete();
            $table->foreignId('production_stage_id')->constrained('production_stages')->restrictOnDelete();
            $table->foreignId('production_operation_run_id')->constrained('production_operation_runs')->restrictOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->string('measure_type', 20);
            $table->decimal('measure_quantity', 18, 4);
            $table->decimal('unit_rate', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->dateTime('worked_at');
            $table->string('status', 20)->default('pending');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'production_id', 'status']);
            $table->index(['production_operation_run_id', 'personnel_id']);
            $table->index(['personnel_id', 'worked_at']);
        });

        Schema::create('production_labor_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labor_entry_id')->unique()->constrained('production_labor_entries')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_id')->constrained('productions')->restrictOnDelete();
            $table->foreignId('production_stage_id')->constrained('production_stages')->restrictOnDelete();
            $table->foreignId('production_operation_run_id')->constrained('production_operation_runs')->restrictOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->string('measure_type', 20);
            $table->decimal('measure_quantity', 18, 4);
            $table->decimal('unit_rate', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->dateTime('worked_at');
            $table->dateTime('approved_at');
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'production_id']);
            $table->index(['production_operation_run_id', 'worked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_labor_costs');
        Schema::dropIfExists('production_labor_entries');
        Schema::dropIfExists('personnel_labor_rates');
        Schema::dropIfExists('personnel');
    }
};