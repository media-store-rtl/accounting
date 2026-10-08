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
        $payload = $this->buildPayload();
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

        $payload = $this->decodeAndValidate($json);
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
        if (! hash_equals($this->schemaHash(), $payload['schema_hash'])) {
            throw new RuntimeException('Restore was rejected: backup schema does not match the current database schema.');
        }

        $connection = $this->database->connection();
        $backupSnapshot = $backup->getAttributes();
        $tables = array_values(array_diff(array_keys($payload['tables']), ['migrations', 'backup_files']));
        $insertOrder = $this->dependencyOrder($tables);
        $deleteOrder = array_reverse($insertOrder);

        try {
            $connection->transaction(function () use ($connection, $payload, $deleteOrder, $insertOrder): void {
                if ($connection->getDriverName() === 'sqlite') {
                    $connection->statement('PRAGMA defer_foreign_keys = ON');
                }
                foreach ($deleteOrder as $table) {
                    $connection->table($table)->delete();
                }

                foreach ($insertOrder as $table) {
                    foreach (array_chunk($payload['tables'][$table]['rows'] ?? [], 500) as $chunk) {
                        if ($chunk !== []) {
                            $connection->table($table)->insert($chunk);
                        }
                    }
                }
            });

            $backupSnapshot['status'] = 'restored';
            DB::table('backup_files')->updateOrInsert(['id'=>$backupSnapshot['id']],$backupSnapshot);
        } catch (Throwable $e) {
            throw new RuntimeException('Restore failed: '.$e->getMessage(), previous: $e);
        } finally {
            try { Schema::enableForeignKeyConstraints(); } catch (Throwable) {}
        }
    }

    private function dependencyOrder(array $tables): array
    {
        $set=array_fill_keys($tables,true);
        $dependencies=[];
        $dependents=array_fill_keys($tables,[]);
        $indegree=array_fill_keys($tables,0);
        foreach($tables as $table){
            $dependencies[$table]=[];
            foreach(Schema::getForeignKeys($table) as $fk){
                $parent=$fk['foreign_table']??null;
                if($parent && $parent!==$table && isset($set[$parent]) && !isset($dependencies[$table][$parent])){
                    $dependencies[$table][$parent]=true;
                    $dependents[$parent][]=$table;
                    $indegree[$table]++;
                }
            }
        }
        $queue=array_values(array_filter($tables,fn($t)=>$indegree[$t]===0));
        $result=[];
        while($queue){
            $table=array_shift($queue); $result[]=$table;
            foreach($dependents[$table] as $child){
                $indegree[$child]--;
                if($indegree[$child]===0)$queue[]=$child;
            }
        }
        foreach($tables as $table) if(!in_array($table,$result,true)) $result[]=$table;
        return $result;
    }

    private function buildPayload(): array
    {
        $tables = [];
        foreach ($this->tableNames() as $table) {
            if (in_array($table, ['migrations', 'backup_files'], true)) {
                continue;
            }

            $tables[$table] = [
                'columns' => $this->columns($table),
                'rows' => $this->database->table($table)->get()->map(fn ($row) => (array) $row)->all(),
            ];
        }

        return [
            'format' => self::FORMAT,
            'created_at' => now()->toIso8601String(),
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

        if (($payload['format'] ?? null) !== self::FORMAT || ! is_array($payload['tables'] ?? null) || ! is_string($payload['schema_hash'] ?? null)) {
            throw new RuntimeException('Invalid backup: unsupported format or missing manifest.');
        }

        foreach ($payload['tables'] as $table => $data) {
            if (! preg_match('/^[A-Za-z0-9_]+$/', (string) $table) || ! is_array($data) || ! is_array($data['columns'] ?? null) || ! is_array($data['rows'] ?? null)) {
                throw new RuntimeException('Invalid backup: malformed table data.');
            }
        }

        return $payload;
    }

    private function tableNames(): array
    {
        return collect(Schema::getTableListing(schemaQualified: false))
            ->map(fn ($name) => is_string($name) ? $name : ($name['name'] ?? null))
            ->filter()->values()->all();
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
