<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('production_section_id')->nullable()->after('order_id')->constrained('production_sections')->nullOnDelete();
            $table->index(['company_id', 'production_section_id'], 'supply_requests_company_section_idx');
        });
    }

    public function down(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropIndex('supply_requests_company_section_idx');
            $table->dropConstrainedForeignId('production_section_id');
        });
    }
};
