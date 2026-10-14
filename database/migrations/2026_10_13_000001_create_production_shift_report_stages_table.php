<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'production_shift_report_stages';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('production_shift_report_id')->constrained('production_shift_reports')->cascadeOnDelete();
                $table->foreignId('production_stage_id')->constrained('production_stages')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['production_shift_report_id', 'production_stage_id'], 'psrs_report_stage_unique');
                $table->index(['company_id', 'production_shift_report_id'], 'psrs_company_report_idx');
            });

            return;
        }

        // A previous/manual deployment may have created this table without
        // recording the migration. Reconcile it in place instead of failing
        // on CREATE TABLE or deleting existing production data.
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Cannot reconcile an existing production_shift_report_stages table on this database driver.'
            );
        }

        foreach ([
            'id',
            'company_id',
            'production_shift_report_id',
            'production_stage_id',
            'created_at',
            'updated_at',
        ] as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                throw new RuntimeException(
                    "Cannot reconcile production_shift_report_stages: required column [{$column}] is missing."
                );
            }
        }

        foreach (['companies', 'production_shift_reports', 'production_stages'] as $parentTable) {
            if (!Schema::hasTable($parentTable)) {
                throw new RuntimeException(
                    "Cannot reconcile production_shift_report_stages: referenced table [{$parentTable}] is missing."
                );
            }
        }

        $this->assertNoOrphans('company_id', 'companies');
        $this->assertNoOrphans('production_shift_report_id', 'production_shift_reports');
        $this->assertNoOrphans('production_stage_id', 'production_stages');

        $duplicate = DB::table(self::TABLE)
            ->select('production_shift_report_id', 'production_stage_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('production_shift_report_id', 'production_stage_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                'Cannot add psrs_report_stage_unique: duplicate report/stage pair found '
                . "(production_shift_report_id={$duplicate->production_shift_report_id}, "
                . "production_stage_id={$duplicate->production_stage_id}). Resolve the duplicate rows without losing required data, then deploy again."
            );
        }

        $foreignKeys = DB::select(
            'SELECT kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME, rc.DELETE_RULE
             FROM information_schema.KEY_COLUMN_USAGE AS kcu
             INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc
                ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
               AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
               AND rc.TABLE_NAME = kcu.TABLE_NAME
             WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
               AND kcu.TABLE_NAME = ?
               AND kcu.REFERENCED_TABLE_NAME IS NOT NULL',
            [self::TABLE]
        );

        $foreignKeyByColumn = [];
        foreach ($foreignKeys as $foreignKey) {
            $foreignKeyByColumn[$foreignKey->COLUMN_NAME] = $foreignKey;
        }

        $expectedForeignKeys = [
            'company_id' => ['table' => 'companies', 'delete' => 'CASCADE'],
            'production_shift_report_id' => ['table' => 'production_shift_reports', 'delete' => 'CASCADE'],
            'production_stage_id' => ['table' => 'production_stages', 'delete' => 'RESTRICT'],
        ];

        $missingForeignKeys = [];
        foreach ($expectedForeignKeys as $column => $expected) {
            $actual = $foreignKeyByColumn[$column] ?? null;

            if (!$actual) {
                $missingForeignKeys[$column] = $expected;
                continue;
            }

            if (
                $actual->REFERENCED_TABLE_NAME !== $expected['table']
                || $actual->REFERENCED_COLUMN_NAME !== 'id'
                || strtoupper($actual->DELETE_RULE) !== $expected['delete']
            ) {
                throw new RuntimeException(
                    "Cannot reconcile production_shift_report_stages: foreign key on [{$column}] does not match the expected reference or delete rule."
                );
            }
        }

        $indexes = DB::select(
            'SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR \',\') AS indexed_columns
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             GROUP BY INDEX_NAME, NON_UNIQUE',
            [self::TABLE]
        );

        $hasUniqueReportStage = false;
        $hasCompanyReportIndex = false;
        foreach ($indexes as $index) {
            if (
                (int) $index->NON_UNIQUE === 0
                && $index->indexed_columns === 'production_shift_report_id,production_stage_id'
            ) {
                $hasUniqueReportStage = true;
            }

            if ($index->indexed_columns === 'company_id,production_shift_report_id') {
                $hasCompanyReportIndex = true;
            }
        }

        Schema::table(self::TABLE, function (Blueprint $table) use (
            $hasUniqueReportStage,
            $hasCompanyReportIndex,
            $missingForeignKeys
        ) {
            if (!$hasUniqueReportStage) {
                $table->unique(
                    ['production_shift_report_id', 'production_stage_id'],
                    'psrs_report_stage_unique'
                );
            }

            if (!$hasCompanyReportIndex) {
                $table->index(
                    ['company_id', 'production_shift_report_id'],
                    'psrs_company_report_idx'
                );
            }

            if (isset($missingForeignKeys['production_shift_report_id'])) {
                $table->foreign('production_shift_report_id', 'production_shift_report_stages_production_shift_report_id_foreign')
                    ->references('id')
                    ->on('production_shift_reports')
                    ->cascadeOnDelete();
            }

            if (isset($missingForeignKeys['production_stage_id'])) {
                $table->foreign('production_stage_id', 'production_shift_report_stages_production_stage_id_foreign')
                    ->references('id')
                    ->on('production_stages')
                    ->restrictOnDelete();
            }

            if (isset($missingForeignKeys['company_id'])) {
                $table->foreign('company_id', 'production_shift_report_stages_company_id_foreign')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            }
        });
    }

    private function assertNoOrphans(string $column, string $parentTable): void
    {
        $orphans = DB::table(self::TABLE . ' as child')
            ->leftJoin($parentTable . ' as parent', 'parent.id', '=', 'child.' . $column)
            ->whereNull('parent.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Cannot add the foreign key for [{$column}]: {$orphans} orphan row(s) reference missing records in [{$parentTable}]. Resolve these rows without losing required data, then deploy again."
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
