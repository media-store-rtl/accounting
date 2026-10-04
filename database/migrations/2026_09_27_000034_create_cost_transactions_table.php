<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cost_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('production_stage_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_operation_run_id')->nullable()->constrained()->nullOnDelete(); $table->string('cost_type',50);
            $table->string('direction',20)->default('debit'); $table->decimal('amount',20,4); $table->decimal('quantity',18,4)->nullable();
            $table->decimal('unit_cost',20,4)->nullable(); $table->string('source_type',100)->nullable(); $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('occurred_at'); $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
            $table->index(['company_id','fiscal_year_id','occurred_at']); $table->index(['production_order_id','cost_type','direction']); $table->index(['source_type','source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_transactions');
    }
};
