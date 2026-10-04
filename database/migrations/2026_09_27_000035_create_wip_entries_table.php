<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wip_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('production_stage_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('production_operation_run_id')->nullable()->constrained()->nullOnDelete(); $table->string('entry_type',30);
            $table->decimal('amount',20,4); $table->decimal('quantity',18,4)->nullable(); $table->timestamp('occurred_at');
            $table->text('description')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
            $table->index(['production_order_id','occurred_at']); $table->index(['production_stage_run_id','production_operation_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_entries');
    }
};
