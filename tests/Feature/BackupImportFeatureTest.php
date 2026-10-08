<?php

namespace Tests\Feature;

use App\Models\BackupFile;
use App\Models\User;
use App\Services\BackupService;
use App\Services\ExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BackupImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function userAndCompany(): array
    {
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Test Account', 'code' => 'ACC-'.uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $companyId = DB::table('companies')->insertGetId([
            'account_id' => $accountId, 'name' => 'Test Company', 'code' => 'CMP-'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountId, 'name' => 'Tester', 'username' => 'tester'.uniqid(),
            'email' => uniqid().'@example.com', 'password' => bcrypt('secret'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId, 'name' => 'Owner', 'slug' => 'owner-'.uniqid(),
            'is_system' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }

        DB::table('company_user')->insert([
            'company_id' => $companyId, 'user_id' => $userId, 'role_id' => $roleId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [User::findOrFail($userId), $companyId];
    }

    public function test_create_backup(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();

        $backup = app(BackupService::class)->create($companyId, $user->id);

        $this->assertSame('ready', $backup->status);
        Storage::disk('local')->assertExists($backup->disk_path);
        $this->assertSame(64, strlen($backup->sha256));
    }

    public function test_upload_backup(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();
        $created = app(BackupService::class)->create($companyId, $user->id);
        $json = Storage::disk('local')->get($created->disk_path);

        $uploaded = app(BackupService::class)->upload(
            $companyId,
            $user->id,
            UploadedFile::fake()->createWithContent('backup.json', $json)
        );

        $this->assertSame('uploaded', $uploaded->type);
        Storage::disk('local')->assertExists($uploaded->disk_path);
    }

    public function test_restore_requires_explicit_confirmation_and_restores_data(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();
        $backup = app(BackupService::class)->create($companyId, $user->id);

        DB::table('companies')->where('id', $companyId)->update(['name' => 'Changed']);
        app(BackupService::class)->restore($backup, 'RESTORE');

        $this->assertSame('Test Company', DB::table('companies')->where('id', $companyId)->value('name'));
        $this->assertSame('restored', BackupFile::findOrFail($backup->id)->status);
    }

    public function test_backup_routes_require_permission(): void
    {
        [$user, $companyId] = $this->userAndCompany();
        DB::table('role_permissions')->delete();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->post('/backups/create')
            ->assertForbidden();
    }

    public function test_excel_mapping_and_validation_succeed_before_import(): void
    {
        Storage::fake('local');
        [, $companyId] = $this->userAndCompany();
        $file = $this->excelFile([
            ['Name', 'Code', 'Symbol', 'Unit Type'],
            ['Kilogram', 'KG-1', 'kg', 'weight'],
        ]);

        $inspection = app(ExcelImportService::class)->inspect($file, $companyId);
        $this->assertSame(['Name', 'Code', 'Symbol', 'Unit Type'], $inspection['columns']);

        $result = app(ExcelImportService::class)->validate($inspection['token'], 'units', [
            'Name' => 'name', 'Code' => 'code', 'Symbol' => 'symbol', 'Unit Type' => 'unit_type',
        ], $companyId);

        $this->assertTrue($result['valid']);
        $this->assertSame(1, $result['rows']);
    }

    public function test_excel_validation_failure_writes_nothing(): void
    {
        Storage::fake('local');
        [, $companyId] = $this->userAndCompany();
        $file = $this->excelFile([
            ['Name', 'Code', 'Symbol', 'Unit Type'],
            ['', 'BAD-1', 'x', 'weight'],
        ]);

        $service = app(ExcelImportService::class);
        $inspection = $service->inspect($file, $companyId);
        $mapping = ['Name' => 'name', 'Code' => 'code', 'Symbol' => 'symbol', 'Unit Type' => 'unit_type'];
        $result = $service->validate($inspection['token'], 'units', $mapping, $companyId);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertDatabaseCount('units', 0);

        $this->expectException(\RuntimeException::class);
        $service->import($inspection['token'], 'units', $mapping, $companyId);
    }

    private function excelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $row => $values) {
            foreach ($values as $column => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column + 1).($row + 1), $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'accounting-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        @unlink($path);
        $spreadsheet->disconnectWorksheets();

        return UploadedFile::fake()->createWithContent('import.xlsx', $contents);
    }
}
