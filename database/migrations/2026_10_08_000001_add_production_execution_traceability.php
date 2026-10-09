<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('production_sections') && !Schema::hasColumn('production_sections', 'is_active')) {
            Schema::table('production_sections', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->index();
            });
        }

        if (Schema::hasTable('production_stages') && !Schema::hasColumn('production_stages', 'production_section_id')) {
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
                $table->decimal('quantity', 18, 4);
                $table->foreignId('source_inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
                $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('recorded_at');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['production_operation_run_id', 'goods_id'], 'poi_operation_goods_idx');
            });
        } elseif (!collect(Schema::getIndexes('production_operation_inputs'))->contains(fn (array $index) => $index['name'] === 'poi_operation_goods_idx')) {
            Schema::table('production_operation_inputs', function (Blueprint $table) {
                $table->index(['production_operation_run_id', 'goods_id'], 'poi_operation_goods_idx');
            });
        }

        if (!Schema::hasTable('production_operation_outputs')) {
            Schema::create('production_operation_outputs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_operation_run_id')->constrained()->cascadeOnDelete();
                $table->foreignId('goods_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->string('output_type', 30)->default('product');
                $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('recorded_at');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['production_operation_run_id', 'goods_id'], 'poo_operation_goods_idx');
            });
        } elseif (!collect(Schema::getIndexes('production_operation_outputs'))->contains(fn (array $index) => $index['name'] === 'poo_operation_goods_idx')) {
            Schema::table('production_operation_outputs', function (Blueprint $table) {
                $table->index(['production_operation_run_id', 'goods_id'], 'poo_operation_goods_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_operation_outputs');
        Schema::dropIfExists('production_operation_inputs');
        if (Schema::hasTable('production_sections') && Schema::hasColumn('production_sections', 'is_active')) {
            Schema::table('production_sections', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
        if (Schema::hasTable('production_stages') && Schema::hasColumn('production_stages', 'production_section_id')) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->dropForeign(['production_section_id']);
                $table->dropColumn('production_section_id');
            });
        }
    }
};
