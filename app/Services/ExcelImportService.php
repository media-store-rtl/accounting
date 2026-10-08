<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RuntimeException;
use Throwable;

class ExcelImportService
{
    private const TARGETS = ['suppliers', 'personnel', 'goods_categories', 'units', 'goods', 'locations', 'customers'];

    public function inspect(UploadedFile $file, int $companyId): array
    {
        $path = $this->storeTemporary($file, $companyId);
        try {
            $spreadsheet = $this->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $headers = $this->headers($sheet);
            return ['token' => $path, 'sheet' => $sheet->getTitle(), 'columns' => $headers, 'targets' => $this->targets(), 'sample' => $this->sample($sheet, $headers)];
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException('Excel file is invalid or cannot be read.', previous: $e);
        }
    }

    public function validate(string $token, string $target, array $mapping, int $companyId): array
    {
        $this->assertTarget($target);
        $path = $this->assertTemporary($token, $companyId);
        $spreadsheet = $this->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->headers($sheet);
        $this->assertMapping($headers, $mapping, $target);

        $rows = [];
        $errors = [];
        $seenCodes = [];
        $highest = $sheet->getHighestDataRow();

        for ($rowNumber = 2; $rowNumber <= $highest; $rowNumber++) {
            $source = [];
            foreach ($headers as $index => $header) {
                $source[$header] = $sheet->getCell(Coordinate::stringFromColumnIndex($index + 1).$rowNumber)->getValue();
            }
            if (count(array_filter($source, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0) continue;

            $mapped = ['company_id' => $companyId];
            foreach ($mapping as $sourceHeader => $targetColumn) {
                if ($targetColumn !== null && $targetColumn !== '') $mapped[$targetColumn] = $source[$sourceHeader] ?? null;
            }

            $rowErrors = $this->validateRow($target, $mapped, $companyId, $rowNumber, $seenCodes);
            if ($rowErrors !== []) $errors[$rowNumber] = $rowErrors;
            else $rows[] = $mapped;
        }

        return ['valid' => $errors === [], 'rows' => count($rows), 'errors' => $errors, 'preview' => array_slice($rows, 0, 20)];
    }

    public function import(string $token, string $target, array $mapping, int $companyId): int
    {
        $result = $this->validate($token, $target, $mapping, $companyId);
        if (! $result['valid']) throw new RuntimeException('Import was rejected because validation failed. No rows were saved.');

        $path = $this->assertTemporary($token, $companyId);
        $spreadsheet = $this->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->headers($sheet);
        $highest = $sheet->getHighestDataRow();
        $count = 0;

        try {
            DB::transaction(function () use (&$count, $sheet, $headers, $highest, $mapping, $companyId, $target): void {
                for ($rowNumber = 2; $rowNumber <= $highest; $rowNumber++) {
                    $source = [];
                    foreach ($headers as $index => $header) $source[$header] = $sheet->getCellByColumnAndRow($index + 1, $rowNumber)->getValue();
                    if (count(array_filter($source, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0) continue;

                    $mapped = ['company_id' => $companyId];
                    foreach ($mapping as $sourceHeader => $targetColumn) {
                        if ($targetColumn !== null && $targetColumn !== '') $mapped[$targetColumn] = $source[$sourceHeader] ?? null;
                    }
                    DB::table($target)->insert($mapped);
                    $count++;
                }
            });
        } catch (QueryException $e) {
            throw new RuntimeException('Import failed and was rolled back: '.$e->getMessage(), previous: $e);
        }

        Storage::disk('local')->delete($path);
        return $count;
    }

    private function targets(): array
    {
        $result = [];
        foreach (self::TARGETS as $target) {
            $result[$target] = collect(Schema::getColumns($target))
                ->pluck('name')
                ->reject(fn ($column) => in_array($column, ['id', 'company_id', 'created_at', 'updated_at'], true))
                ->values()->all();
        }
        return $result;
    }

    private function validateRow(string $target, array $row, int $companyId, int $rowNumber, array &$seenCodes): array
    {
        $errors = [];
        $columns = collect(Schema::getColumns($target));

        foreach ($this->targets()[$target] as $column) {
            $meta = $columns->firstWhere('name', $column);
            $mapped = array_key_exists($column, $row);
            $value = $row[$column] ?? null;
            $hasDefault = array_key_exists('default', $meta ?? []) && $meta['default'] !== null;
            if (!$mapped && (($meta['nullable'] ?? false) || $hasDefault || $column === 'is_active')) {
                continue;
            }
            if (! (bool) ($meta['nullable'] ?? false) && ($value === null || trim((string) $value) === '')) {
                $errors[] = "$column is required";
                continue;
            }
            $type = strtolower((string) ($meta['type'] ?? ''));
            if ($value !== null && $value !== '' && preg_match('/int|decimal|numeric|float|double/', $type) && ! is_numeric($value)) {
                $errors[] = "$column must be numeric";
            }
        }

        if (isset($row['code']) && $row['code'] !== null && $row['code'] !== '') {
            if (isset($seenCodes[(string) $row['code']])) $errors[] = 'code is duplicated in the import file';
            else $seenCodes[(string) $row['code']] = $rowNumber;

            if (DB::table($target)->where('company_id', $companyId)->where('code', $row['code'])->exists()) {
                $errors[] = 'code already exists';
            }
        }

        foreach (Schema::getForeignKeys($target) as $foreignKey) {
            $column = $foreignKey['columns'][0] ?? null;
            $foreignTable = $foreignKey['foreign_table'] ?? null;
            $foreignColumn = $foreignKey['foreign_columns'][0] ?? 'id';
            if ($column && $foreignTable && array_key_exists($column, $row) && $row[$column] !== null && $row[$column] !== '') {
                if (! DB::table($foreignTable)->where($foreignColumn, $row[$column])->exists()) $errors[] = $column.' references a missing record';
            }
        }

        return $errors;
    }

    private function assertMapping(array $headers, array $mapping, string $target): void
    {
        $allowed = $this->targets()[$target];
        $unknown = array_diff(array_values(array_filter($mapping, fn ($value) => $value !== null && $value !== '')), $allowed);
        if ($unknown !== []) throw new RuntimeException('Mapping contains unknown target columns: '.implode(', ', $unknown));

        foreach ($mapping as $source => $targetColumn) {
            if (! in_array($source, $headers, true)) throw new RuntimeException("Mapping references unknown Excel column: {$source}");
            if ($targetColumn !== null && $targetColumn !== '' && ! in_array($targetColumn, $allowed, true)) {
                throw new RuntimeException("Target column {$targetColumn} is not allowed.");
            }
        }
    }

    private function assertTarget(string $target): void
    {
        if (! in_array($target, self::TARGETS, true)) throw new RuntimeException('This entity is not supported for Excel import.');
    }

    private function storeTemporary(UploadedFile $file, int $companyId): string
    {
        if (! $file->isValid() || $file->getSize() > 25 * 1024 * 1024) throw new RuntimeException('Excel file is invalid or exceeds the 25 MB limit.');

        try {
            $type = IOFactory::identify($file->getRealPath());
            if (! in_array(strtolower($type), ['xlsx', 'xls', 'ods', 'csv'], true)) throw new RuntimeException('Unsupported spreadsheet format.');
        } catch (Throwable $e) {
            throw new RuntimeException('The uploaded file is not a valid spreadsheet.', previous: $e);
        }

        $path = $file->storeAs('imports/'.$companyId, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
        if (! $path) throw new RuntimeException('Could not store the uploaded spreadsheet.');
        return $path;
    }

    private function assertTemporary(string $token, int $companyId): string
    {
        if (! str_starts_with($token, 'imports/'.$companyId.'/') || str_contains($token, '..') || ! Storage::disk('local')->exists($token)) {
            throw new RuntimeException('Import session is invalid or expired.');
        }
        return $token;
    }

    private function load(string $path)
    {
        $absolute = Storage::disk('local')->path($path);
        $reader = IOFactory::createReader(IOFactory::identify($absolute));
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        return $reader->load($absolute);
    }

    private function headers($sheet): array
    {
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headers = [];
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($i). '1')->getValue());
            if ($value === '') continue;
            if (in_array($value, $headers, true)) throw new RuntimeException("Duplicate Excel column: {$value}");
            $headers[] = $value;
        }
        if ($headers === []) throw new RuntimeException('Excel file has no header row.');
        return $headers;
    }

    private function sample($sheet, array $headers): array
    {
        $sample = [];
        for ($row = 2; $row <= min(6, $sheet->getHighestDataRow()); $row++) {
            $item = [];
            foreach ($headers as $index => $header) $item[$header] = $sheet->getCell(Coordinate::stringFromColumnIndex($index + 1).$row)->getValue();
            $sample[] = $item;
        }
        return $sample;
    }
}
