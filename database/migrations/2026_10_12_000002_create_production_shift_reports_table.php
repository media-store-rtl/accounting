<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_section_id')->constrained('production_sections')->restrictOnDelete();
            $table->foreignId('goods_id')->constrained('goods')->restrictOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('shift_name', 80);
            $table->decimal('worked_hours', 6, 2)->default(8);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('holiday_hours', 6, 2)->default(0);
            $table->date('work_date');
            $table->decimal('produced_quantity', 18, 4);
            $table->decimal('scrap_quantity', 18, 4)->default(0);
            $table->decimal('warehouse_quantity', 18, 4)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('warehouse_received_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'work_date'], 'prod_shift_reports_company_status_date_idx');
            $table->index(['production_section_id', 'work_date'], 'prod_shift_reports_section_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_shift_reports');
    }
};
