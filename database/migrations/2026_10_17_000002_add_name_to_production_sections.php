<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_sections', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('production_sections as ps')
            ->join('locations as l', 'l.id', '=', 'ps.location_id')
            ->select('ps.id', 'l.name as location_name')
            ->orderBy('ps.id')
            ->get()
            ->each(function ($section) {
                DB::table('production_sections')
                    ->where('id', $section->id)
                    ->update(['name' => $section->location_name]);
            });
    }

    public function down(): void
    {
        Schema::table('production_sections', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
