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

    public function test_invalid_backup_is_rejected(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();

        $this->expectException(\RuntimeException::class);
        app(BackupService::class)->upload(
            $companyId,
            $user->id,
            UploadedFile::fake()->createWithContent('invalid.json', '{"format":"not-a-backup"}')
        );
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

    public function test_backup_and_excel_pages_are_reachable_with_permission(): void
    {
        [$user, $companyId] = $this->userAndCompany();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->get('/backups')
            ->assertOk()
            ->assertSee('پشتیبان‌گیری و بازیابی');

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->get('/imports/excel')
            ->assertOk()
            ->assertSee('ورود اطلاعات از Excel');
    }

    public function test_backup_routes_require_permission(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();
        DB::table('role_permissions')->delete();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->get('/backups')
            ->assertForbidden();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->post('/backups/create')
            ->assertForbidden();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->post('/backups/upload')
            ->assertForbidden();

        $backup = BackupFile::create([
            'company_id' => $companyId, 'user_id' => $user->id, 'type' => 'created',
            'original_name' => 'test.json', 'disk_path' => 'backups/test.json',
            'size' => 1, 'sha256' => str_repeat('a', 64), 'schema_hash' => str_repeat('b', 64),
            'status' => 'ready',
        ]);

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->post('/backups/'.$backup->id.'/restore', ['confirmation' => 'RESTORE'])
            ->assertForbidden();
    }

    public function test_excel_route_requires_permission(): void
    {
        [$user, $companyId] = $this->userAndCompany();
        DB::table('role_permissions')->delete();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->get('/imports/excel')
            ->assertForbidden();

        $this->actingAs($user)->withSession(['company_id' => $companyId])
            ->post('/imports/excel/inspect')
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

    public function test_excel_mapping_rejects_duplicate_target_columns(): void
    {
        Storage::fake('local');
        [, $companyId] = $this->userAndCompany();
        $file = $this->excelFile([
            ['Name', 'Name 2', 'Code', 'Symbol', 'Unit Type'],
            ['Kilogram', 'KG duplicate', 'KG-2', 'kg', 'weight'],
        ]);

        $service = app(ExcelImportService::class);
        $inspection = $service->inspect($file, $companyId);

        $this->expectException(\RuntimeException::class);
        $service->validate($inspection['token'], 'units', [
            'Name' => 'name', 'Name 2' => 'name', 'Code' => 'code',
            'Symbol' => 'symbol', 'Unit Type' => 'unit_type',
        ], $companyId);
    }

    public function test_excel_import_rolls_back_when_database_constraint_fails(): void
    {
        Storage::fake('local');
        [$user, $companyId] = $this->userAndCompany();

        $file = $this->excelFile([
            ['Code', 'Name', 'user_id'],
            ['P-1', 'Person One', $user->id],
            ['P-2', 'Person Two', $user->id],
        ]);

        $service = app(ExcelImportService::class);
        $inspection = $service->inspect($file, $companyId);
        $mapping = ['Code' => 'code', 'Name' => 'name', 'user_id' => 'user_id'];

        try {
            $service->import($inspection['token'], 'personnel', $mapping, $companyId);
            $this->fail('Import should fail on the second personnel row.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('rolled back', $e->getMessage());
        }

        $this->assertDatabaseCount('personnel', 0);
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
                $sheet->setCellValueByColumnAndRow($column + 1, $row + 1, $value);
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
