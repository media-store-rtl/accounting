<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_shift_reports', function (Blueprint $table) {
            $table->foreignId('personnel_id')->nullable()->after('goods_id')->constrained('personnel')->restrictOnDelete();
            $table->index(['company_id', 'personnel_id', 'work_date'], 'prod_shift_reports_worker_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_shift_reports', function (Blueprint $table) {
            $table->dropIndex('prod_shift_reports_worker_date_idx');
            $table->dropConstrainedForeignId('personnel_id');
        });
    }
};
