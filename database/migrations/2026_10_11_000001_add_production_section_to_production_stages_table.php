<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('production_stages', 'production_section_id')) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->unsignedBigInteger('production_section_id')->nullable()->after('production_route_id');
            });
        }

        // A failed MySQL migration may leave the column behind without recording
        // the migration. Ensure the foreign key exists independently of the column.
        if (DB::getDriverName() === 'mysql') {
            $foreignKeyExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'production_stages')
                ->where('COLUMN_NAME', 'production_section_id')
                ->where('REFERENCED_TABLE_NAME', 'production_sections')
                ->exists();
        } else {
            $foreignKeyExists = false;
        }

        if (! $foreignKeyExists) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->foreign('production_section_id')
                    ->references('id')
                    ->on('production_sections')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('production_stages', function (Blueprint $table) {
            $table->dropForeign(['production_section_id']);
        });

        Schema::table('production_stages', function (Blueprint $table) {
            $table->dropColumn('production_section_id');
        });
    }
};
