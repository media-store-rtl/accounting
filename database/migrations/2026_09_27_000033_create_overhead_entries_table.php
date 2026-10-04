<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('overhead_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('production_stage_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_operation_run_id')->nullable()->constrained()->nullOnDelete(); $table->string('category',100);
            $table->string('source_reference',150)->nullable(); $table->decimal('amount',20,4); $table->timestamp('allocated_at');
            $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
            $table->index(['company_id','fiscal_year_id','allocated_at']); $table->index(['production_order_id','category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overhead_entries');
    }
};
