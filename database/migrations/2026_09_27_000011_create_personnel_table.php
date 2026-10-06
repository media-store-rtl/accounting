<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('national_id', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};