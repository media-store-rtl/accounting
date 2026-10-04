<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->string('code',100); $table->string('name');
            $table->string('machine_type',100)->nullable(); $table->decimal('hourly_rate',20,4)->nullable(); $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable(); $table->timestamps(); $table->unique(['company_id','code']); $table->index(['company_id','is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
