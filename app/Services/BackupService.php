<?php

namespace App\Services;

use App\Models\BackupFile;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BackupService
{
    private const FORMAT = 'accounting-company-backup-v2';

    private const EXCLUDED_TABLES = [
        'migrations',
        'backup_files',
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'company_user',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'notifications',
        'personal_access_tokens',
        'password_reset_tokens',
    ];

    public function __construct(private DatabaseManager $database)
    {
    }

    public function create(int $companyId, int $userId): BackupFile
    {
        $payload = $this->buildPayload($companyId);
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $name = 'accounting-backup-'.$companyId.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.json';
        $path = 'backups/'.$companyId.'/'.$name;

        Storage::disk('local')->put($path, $json);

        return BackupFile::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => 'created',
            'original_name' => $name,
            'disk_path' => $path,
            'size' => strlen($json),
            'sha256' => hash('sha256', $json),
            'schema_hash' => $payload['schema_hash'],
            'status' => 'ready',
        ]);
    }

    public function upload(int $companyId, int $userId, UploadedFile $file): BackupFile
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Backup upload failed: the uploaded file is invalid.');
        }

        $json = file_get_contents($file->getRealPath());

        if ($json === false || strlen($json) > 100 * 1024 * 1024) {
            throw new RuntimeException('Backup upload failed: the file is unreadable or exceeds the 100 MB limit.');
        }

        $payload = $this->decodeAndValidate($json);

        if ((int) ($payload['scope']['company_id'] ?? 0) !== $companyId) {
            throw new RuntimeException('Backup upload rejected: this backup belongs to another company.');
        }

        $name = 'uploaded-'.Str::uuid().'.json';
        $path = 'backups/'.$companyId.'/'.$name;
        Storage::disk('local')->put($path, $json);

        return BackupFile::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => 'uploaded',
            'original_name' => $file->getClientOriginalName(),
            'disk_path' => $path,
            'size' => strlen($json),
            'sha256' => hash('sha256', $json),
            'schema_hash' => $payload['schema_hash'],
            'status' => 'ready',
        ]);
    }

    public function restore(BackupFile $backup, string $confirmation): void
    {
        if ($backup->status !== 'ready' || $confirmation !== 'RESTORE') {
            throw new RuntimeException('Restore was rejected. A ready backup and explicit RESTORE confirmation are required.');
        }

        $json = Storage::disk('local')->get($backup->disk_path);

        if (hash('sha256', $json) !== $backup->sha256) {
            throw new RuntimeException('Restore was rejected: backup checksum does not match.');
        }

        $payload = $this->decodeAndValidate($json);
        $companyId = (int) $backup->company_id;

        if ((int) ($payload['scope']['company_id'] ?? 0) !== $companyId) {
            throw new RuntimeException('Restore was rejected: backup company does not match the selected company.');
        }

        if (! hash_equals($this->schemaHash(), $payload['schema_hash'])) {
            throw new RuntimeException('Restore was rejected: backup schema does not match the current database schema.');
        }

        $this->assertRestorableTables($payload['tables'], $companyId);

        $connection = $this->database->connection();
        $tables = array_keys($payload['tables']);

        try {
            $connection->transaction(function () use ($connection, $payload, $tables, $companyId): void {
                Schema::disableForeignKeyConstraints();

                foreach (array_reverse($tables) as $table) {
                    $this->deleteScopedRows($connection, $table, $companyId);
                }

                foreach ($this->restoreOrder($tables) as $table) {
                    foreach (array_chunk($payload['tables'][$table]['rows'] ?? [], 500) as $chunk) {
                        if ($chunk !== []) {
                            $connection->table($table)->insert($chunk);
                        }
                    }
                }

                Schema::enableForeignKeyConstraints();
            });

            $backup->update(['status' => 'restored']);
        } catch (Throwable $e) {
            try {
                Schema::enableForeignKeyConstraints();
            } catch (Throwable) {
            }

            throw new RuntimeException('Restore failed and was rolled back: '.$e->getMessage(), previous: $e);
        }
    }

    private function buildPayload(int $companyId): array
    {
        $tables = [];

        foreach ($this->restorableTableNames() as $table) {
            $columns = $this->columns($table);

            if ($table === 'companies') {
                $rows = $this->database->table($table)->where('id', $companyId)->get()
                    ->map(fn ($row) => (array) $row)->all();
            } else {
                $rows = $this->database->table($table)->where('company_id', $companyId)->get()
                    ->map(fn ($row) => (array) $row)->all();
            }

            $tables[$table] = [
                'columns' => $columns,
                'rows' => $rows,
            ];
        }

        return [
            'format' => self::FORMAT,
            'created_at' => now()->toIso8601String(),
            'scope' => [
                'type' => 'company',
                'company_id' => $companyId,
            ],
            'connection' => config('database.default'),
            'schema_hash' => $this->schemaHash(),
            'tables' => $tables,
        ];
    }

    private function decodeAndValidate(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid backup: file is not valid JSON.', previous: $e);
        }

        if (
            ($payload['format'] ?? null) !== self::FORMAT
            || ! is_array($payload['scope'] ?? null)
            || ($payload['scope']['type'] ?? null) !== 'company'
            || ! is_numeric($payload['scope']['company_id'] ?? null)
            || ! is_array($payload['tables'] ?? null)
            || ! is_string($payload['schema_hash'] ?? null)
        ) {
            throw new RuntimeException('Invalid backup: unsupported format or missing manifest.');
        }

        foreach ($payload['tables'] as $table => $data) {
            if (
                ! preg_match('/^[A-Za-z0-9_]+$/', (string) $table)
                || ! is_array($data)
                || ! is_array($data['columns'] ?? null)
                || ! is_array($data['rows'] ?? null)
            ) {
                throw new RuntimeException('Invalid backup: malformed table data.');
            }

            foreach ($data['rows'] as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException('Invalid backup: a table contains a malformed row.');
                }
            }
        }

        return $payload;
    }

    private function assertRestorableTables(array $tables, int $companyId): void
    {
        $allowed = $this->restorableTableNames();

        foreach ($tables as $table => $data) {
            if (! in_array($table, $allowed, true)) {
                throw new RuntimeException("Restore rejected: table {$table} is outside the company backup scope.");
            }

            $actualColumns = array_column($this->columns($table), 'name');
            $backupColumns = array_column($data['columns'], 'name');

            if ($actualColumns !== $backupColumns) {
                throw new RuntimeException("Restore rejected: column definition for {$table} does not match the current schema.");
            }

            foreach ($data['rows'] as $row) {
                if ($table === 'companies') {
                    if ((int) ($row['id'] ?? 0) !== $companyId) {
                        throw new RuntimeException('Restore rejected: backup contains a company outside the selected scope.');
                    }
                } elseif ((int) ($row['company_id'] ?? 0) !== $companyId) {
                    throw new RuntimeException("Restore rejected: table {$table} contains a row outside the selected company.");
                }
            }
        }
    }

    private function deleteScopedRows($connection, string $table, int $companyId): void
    {
        if ($table === 'companies') {
            $connection->table($table)->where('id', $companyId)->delete();
            return;
        }

        $connection->table($table)->where('company_id', $companyId)->delete();
    }

    private function restoreOrder(array $tables): array
    {
        usort($tables, function (string $a, string $b): int {
            if ($a === 'companies') {
                return -1;
            }

            if ($b === 'companies') {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $tables;
    }

    private function restorableTableNames(): array
    {
        $tables = [];

        foreach ($this->tableNames() as $table) {
            if (in_array($table, self::EXCLUDED_TABLES, true)) {
                continue;
            }

            if ($table === 'companies' || Schema::hasColumn($table, 'company_id')) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private function tableNames(): array
    {
        return collect(Schema::getTableListing(schemaQualified: false))
            ->map(fn ($name) => is_string($name) ? $name : ($name['name'] ?? null))
            ->filter()
            ->values()
            ->all();
    }

    private function columns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->map(fn ($column) => [
                'name' => $column['name'] ?? null,
                'type' => $column['type'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function schemaHash(): string
    {
        $schema = [];

        foreach ($this->tableNames() as $table) {
            if (in_array($table, self::EXCLUDED_TABLES, true)) {
                continue;
            }

            $schema[$table] = $this->columns($table);
        }

        ksort($schema);

        return hash(
            'sha256',
            json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }
}
