<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('production_stages') && !Schema::hasColumn('production_stages','production_section_id')) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->foreignId('production_section_id')->nullable()->after('production_route_id')->constrained('production_sections')->restrictOnDelete();
                $table->index('production_section_id');
            });
        }

        if (!Schema::hasTable('production_operation_inputs')) {
            Schema::create('production_operation_inputs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_operation_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goods_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity',18,4);
                $table->foreignId('source_inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
                $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('recorded_at');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['production_operation_run_id','goods_id']);
            });
        }

        if (!Schema::hasTable('production_operation_outputs')) {
            Schema::create('production_operation_outputs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_operation_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goods_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity',18,4);
                $table->string('output_type',30)->default('product');
                $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('recorded_at');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['production_operation_run_id','goods_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_operation_outputs');
        Schema::dropIfExists('production_operation_inputs');
        if (Schema::hasTable('production_stages') && Schema::hasColumn('production_stages','production_section_id')) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->dropForeign(['production_section_id']);
                $table->dropColumn('production_section_id');
            });
        }
    }
};
