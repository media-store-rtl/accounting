<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_stages', function (Blueprint $table) {
            $table->foreignId('production_section_id')->nullable()->after('production_route_id')->constrained('production_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_stages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_section_id');
        });
    }
};
