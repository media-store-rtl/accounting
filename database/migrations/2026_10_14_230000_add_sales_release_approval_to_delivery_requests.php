<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_approved_by_user_id')->nullable()->after('requested_by_user_id');
            $table->timestamp('sales_approved_at')->nullable()->after('sales_approved_by_user_id');
        });

        // Keep the permission visible under Warehouse and add a distinct Sales approval permission.
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'delivery_request.create'],
            ['name' => 'ثبت درخواست تحویل', 'module' => 'warehouse', 'created_at' => now(), 'updated_at' => now()]
        );
        $createPermissionId = DB::table('permissions')->where('slug', 'delivery_request.create')->value('id');

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'delivery_request.sales_approve'],
            ['name' => 'صدور مجوز خروج از سمت فروش', 'module' => 'sales', 'created_at' => now(), 'updated_at' => now()]
        );

        $approvalPermissionId = DB::table('permissions')->where('slug', 'delivery_request.sales_approve')->value('id');
        $warehouseRoleIds = DB::table('roles')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))->from('role_permissions as rp')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->whereColumn('rp.role_id', 'roles.id')
                    ->whereIn('p.slug', ['delivery_request.view', 'delivery_request.issue', 'warehouse.delivery.manage']);
            })
            ->pluck('id');

        foreach ($warehouseRoleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $createPermissionId,
            ]);
        }

        $salesRoleIds = DB::table('roles')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))->from('role_permissions as rp')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->whereColumn('rp.role_id', 'roles.id')
                    ->where('p.slug', 'order.view');
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))->from('role_permissions as rp')
                    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                    ->whereColumn('rp.role_id', 'roles.id')
                    ->where('p.slug', 'order.create');
            })
            ->pluck('id');

        foreach ($salesRoleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $approvalPermissionId,
            ]);
        }
    }

    public function down(): void
    {
        $approvalPermissionId = DB::table('permissions')->where('slug', 'delivery_request.sales_approve')->value('id');
        if ($approvalPermissionId) {
            DB::table('role_permissions')->where('permission_id', $approvalPermissionId)->delete();
            DB::table('permissions')->where('id', $approvalPermissionId)->delete();
        }

        DB::table('permissions')->where('slug', 'delivery_request.create')->update([
            'module' => 'sales',
            'updated_at' => now(),
        ]);

        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn(['sales_approved_by_user_id', 'sales_approved_at']);
        });
    }
};
