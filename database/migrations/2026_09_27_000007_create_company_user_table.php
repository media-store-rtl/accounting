<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete(); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->primary(['company_id','user_id']); $table->index(['user_id','is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
