<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizational_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('organizational_department_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('organizational_departments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['department_id', 'user_id']);
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('notification_recipient_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 120);
            $table->string('name', 180);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_configured')->default(false);
            $table->boolean('exclude_actor')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'event_key']);
            $table->index(['company_id', 'is_active']);
        });

        Schema::create('notification_recipient_rule_departments', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('notification_recipient_rules')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('organizational_departments')->cascadeOnDelete();
            $table->primary(['rule_id', 'department_id']);
        });

        Schema::create('notification_recipient_rule_users', function (Blueprint $table) {
            $table->foreignId('rule_id')->constrained('notification_recipient_rules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['rule_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipient_rule_users');
        Schema::dropIfExists('notification_recipient_rule_departments');
        Schema::dropIfExists('notification_recipient_rules');
        Schema::dropIfExists('organizational_department_user');
        Schema::dropIfExists('organizational_departments');
    }
};