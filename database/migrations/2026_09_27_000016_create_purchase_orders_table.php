<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('fiscal_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete(); $table->string('number',50); $table->date('ordered_at');
            $table->string('status',30)->default('draft'); $table->decimal('subtotal',18,4)->default(0); $table->decimal('tax_total',18,4)->default(0);
            $table->decimal('discount_total',18,4)->default(0); $table->decimal('grand_total',18,4)->default(0); $table->text('notes')->nullable();
            $table->timestamps(); $table->unique(['company_id','number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
