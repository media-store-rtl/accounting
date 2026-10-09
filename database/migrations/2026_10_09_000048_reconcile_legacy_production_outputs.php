<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Production has an older production_outputs schema left by a removed migration.
        // Preserve it intact under a legacy name instead of dropping or rewriting data.
        if (Schema::hasTable('production_outputs') && !Schema::hasColumn('production_outputs', 'goods_id')) {
            $legacy = 'production_outputs_legacy_20261009';

            if (!Schema::hasTable($legacy)) {
                Schema::rename('production_outputs', $legacy);
            }
        }

        if (!Schema::hasTable('production_outputs')) {
            Schema::create('production_outputs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('production_id')->constrained('productions')->restrictOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
                $table->foreignId('goods_id')->constrained('goods')->restrictOnDelete();
                $table->foreignId('warehouse_location_id')->constrained('locations')->restrictOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->string('status', 30)->default('pending');
                $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('produced_at');
                $table->timestamp('confirmed_at')->nullable();
                $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'status']);
                $table->index(['production_id', 'status']);
                $table->index(['order_id', 'status']);
            });
        }

        if (!Schema::hasTable('finished_goods_receipts')) {
            Schema::create('finished_goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('production_output_id')->unique()->constrained('production_outputs')->cascadeOnDelete();
                $table->foreignId('warehouse_location_id')->constrained('locations')->restrictOnDelete();
                $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->timestamp('received_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive rollback of reconciled production data.
    }
};
