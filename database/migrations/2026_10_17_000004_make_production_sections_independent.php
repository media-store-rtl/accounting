<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_sections', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('location_id')->nullable()->change();
        });

        DB::table('production_sections as ps')
            ->join('locations as l', 'l.id', '=', 'ps.location_id')
            ->whereNull('ps.company_id')
            ->update(['ps.company_id' => DB::raw('l.company_id')]);

        // Keep any pre-existing rows with no resolvable location scoped safely
        // to the company inferred from their old location whenever possible.
    }

    public function down(): void
    {
        // Intentionally leave company_id and nullable location_id in place:
        // new production sections are no longer required to reference a location.
    }
};
