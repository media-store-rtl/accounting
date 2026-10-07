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
    private const FORMAT = 'accounting-logical-backup-v1';

    public function __construct(private DatabaseManager $database)
    {
    }

    public function create(int $companyId, int $userId): BackupFile
    {
        $payload = $this->buildPayload($companyId);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $name = 'accounting-backup-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.json';
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

        $payload = $this->decodeAndValidate($json, $companyId);
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

    public function restore(BackupFile $backup, string $confirmation, int $companyId): void
    {
        if ($backup->company_id !== $companyId) {
            throw new RuntimeException('Restore was rejected: backup belongs to another company.');
        }

        if ($backup->status !== 'ready' || $confirmation !== 'RESTORE') {
            throw new RuntimeException('Restore was rejected. A ready backup and explicit RESTORE confirmation are required.');
        }

        $json = Storage::disk('local')->get($backup->disk_path);
        if (hash('sha256', $json) !== $backup->sha256) {
            throw new RuntimeException('Restore was rejected: backup checksum does not match.');
        }

        $payload = $this->decodeAndValidate($json, $companyId);
        if (! hash_equals($this->schemaHash(), $payload['schema_hash'])) {
            throw new RuntimeException('Restore was rejected: backup schema does not match the current database schema.');
        }

        $connection = $this->database->connection();
        $companyId = (int) $payload['tenant']['company_id'];
        $tables = array_values(array_filter(
            array_diff(array_keys($payload['tables']), ['migrations', 'backup_files']),
            fn (string $table): bool => $this->isRestorableTable($table, $companyId)
        ));

        try {
            $driver = $connection->getDriverName();
            if ($driver === 'sqlite') {
                $connection->statement('PRAGMA foreign_keys = OFF');
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            } else {
                $connection->getSchemaBuilder()->disableForeignKeyConstraints();
            }

            try {
                $connection->transaction(function () use ($connection, $payload, $tables, $companyId): void {
                    foreach (array_reverse($tables) as $table) {
                        $this->scopedQuery($table, $connection->table($table), $companyId)->delete();
                    }

                    foreach ($tables as $table) {
                        foreach (array_chunk($payload['tables'][$table]['rows'] ?? [], 500) as $chunk) {
                            if ($chunk !== []) {
                                $connection->table($table)->insert($chunk);
                            }
                        }
                    }
                });
            } finally {
                if ($driver === 'sqlite') {
                    $connection->statement('PRAGMA foreign_keys = ON');
                } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                    $connection->statement('SET FOREIGN_KEY_CHECKS=1');
                } else {
                    $connection->getSchemaBuilder()->enableForeignKeyConstraints();
                }
            }

            $backup->update(['status' => 'restored']);
        } catch (Throwable $e) {
            throw new RuntimeException('Restore failed: '.$e->getMessage(), previous: $e);
        }
    }

    private function buildPayload(int $companyId): array
    {
        $tables = [];
        foreach ($this->tableNames() as $table) {
            if (in_array($table, ['migrations', 'backup_files'], true)) {
                continue;
            }

            $query = $this->scopedQuery($table, $this->database->table($table), $companyId);
            $tables[$table] = [
                'columns' => $this->columns($table),
                'rows' => $query ? $query->get()->map(fn ($row) => (array) $row)->all() : [],
            ];
        }

        $accountId = $this->database->table('companies')->where('id', $companyId)->value('account_id');
        if (! $accountId) {
            throw new RuntimeException('Backup creation failed: company does not belong to a valid account.');
        }

        return [
            'format' => self::FORMAT,
            'created_at' => now()->toIso8601String(),
            'connection' => config('database.default'),
            'tenant' => ['company_id' => $companyId, 'account_id' => (int) $accountId],
            'schema_hash' => $this->schemaHash(),
            'tables' => $tables,
        ];
    }

    private function decodeAndValidate(string $json, ?int $companyId = null): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new RuntimeException('Invalid backup: file is not valid JSON.', previous: $e);
        }

        if (($payload['format'] ?? null) !== self::FORMAT || ! is_array($payload['tables'] ?? null) || ! is_string($payload['schema_hash'] ?? null) || ! is_array($payload['tenant'] ?? null)) {
            throw new RuntimeException('Invalid backup: unsupported format or missing manifest.');
        }

        if ($companyId !== null) {
            $accountId = $this->database->table('companies')->where('id', $companyId)->value('account_id');
            if ((int) ($payload['tenant']['company_id'] ?? 0) !== $companyId || (int) ($payload['tenant']['account_id'] ?? 0) !== (int) $accountId) {
                throw new RuntimeException('Invalid backup: backup tenant does not match the current company and account.');
            }
        }

        $expectedTables = array_values(array_diff($this->tableNames(), ['migrations', 'backup_files']));
        $actualTables = array_keys($payload['tables']);
        sort($expectedTables);
        sort($actualTables);

        if ($expectedTables !== $actualTables) {
            throw new RuntimeException('Invalid backup: table manifest does not match the current database.');
        }

        foreach ($payload['tables'] as $table => $data) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $table) || ! is_array($data) || ! is_array($data['columns'] ?? null) || ! is_array($data['rows'] ?? null)) {
                throw new RuntimeException('Invalid backup: malformed table data.');
            }

            $expectedColumns = $this->columns($table);
            if ($expectedColumns !== $data['columns']) {
                throw new RuntimeException("Invalid backup: schema definition for {$table} does not match the current database.");
            }

            $columnNames = collect($expectedColumns)->pluck('name')->filter()->all();
            foreach ($data['rows'] as $row) {
                if (! is_array($row) || array_diff(array_keys($row), $columnNames) !== []) {
                    throw new RuntimeException("Invalid backup: row data for {$table} contains unknown columns.");
                }
            }
        }

        return $payload;
    }

    private function isRestorableTable(string $table, int $companyId): bool
    {
        return ! in_array($table, ['accounts', 'users', 'personnel', 'permissions', 'notifications'], true)
            && $this->scopedQuery($table, $this->database->table($table), $companyId) !== null;
    }

    private function scopedQuery(string $table, $query, int $companyId, array $visited = [])
    {
        if (in_array($table, $visited, true)) {
            return null;
        }
        $visited[] = $table;

        $columns = collect(Schema::getColumns($table))->pluck('name')->all();
        if ($table === 'companies') {
            return $query->where('id', $companyId);
        }
        if (in_array('company_id', $columns, true)) {
            return $query->where('company_id', $companyId);
        }

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $column = $foreignKey['columns'][0] ?? null;
            $foreignTable = $foreignKey['foreign_table'] ?? null;
            $foreignColumn = $foreignKey['foreign_columns'][0] ?? 'id';
            if (! $column || ! $foreignTable || ! in_array($column, $columns, true)) {
                continue;
            }

            $parent = $this->scopedQuery($foreignTable, $this->database->table($foreignTable), $companyId, $visited);
            if ($parent !== null) {
                return $query->whereIn($column, $parent->select($foreignColumn));
            }
        }

        return null;
    }

    private function tableNames(): array
    {
        return collect(Schema::getTableListing(schemaQualified: false))
            ->map(fn ($name) => is_string($name) ? $name : ($name['name'] ?? null))
            ->filter(fn ($name) => is_string($name) && ! str_starts_with($name, 'sqlite_'))
            ->values()->all();
    }

    private function columns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->map(fn ($column) => ['name' => $column['name'] ?? null, 'type' => $column['type'] ?? null])
            ->values()->all();
    }

    private function schemaHash(): string
    {
        $schema = [];
        foreach ($this->tableNames() as $table) {
            if (in_array($table, ['migrations', 'backup_files'], true)) continue;
            $schema[$table] = $this->columns($table);
        }
        ksort($schema);
        return hash('sha256', json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
