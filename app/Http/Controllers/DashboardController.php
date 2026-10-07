<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        $companies = $user->companies()
            ->where('companies.is_active', true)
            ->orderBy('companies.name')
            ->get();

        $requestedCompanyId = (int) request()->query('company');
        $sessionCompanyId = (int) session('company_id');
        $companyId = $requestedCompanyId ?: $sessionCompanyId;

        if (! $companyId || ! $companies->contains('id', $companyId)) {
            $companyId = $companies->first()?->id;
        }

        if ($companyId) {
            session(['company_id' => $companyId]);
        } else {
            session()->forget('fiscal_year_id');
        }

        $company = $companies->firstWhere('id', $companyId);
        $fiscalYears = $company?->fiscalYears()->orderByDesc('starts_at')->get() ?? collect();

        $fiscalYearId = (int) session('fiscal_year_id');
        $fiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);

        if (! $fiscalYear || $fiscalYear->is_closed) {
            $fiscalYear = $fiscalYears->first(fn ($year) => ! $year->is_closed);

            if ($fiscalYear) {
                session(['fiscal_year_id' => $fiscalYear->id]);
            } else {
                session()->forget('fiscal_year_id');
            }
        }

        $stats = [
            'inventory_quantity' => 0,
            'open_orders' => 0,
            'shortage_requests' => 0,
            'active_productions' => 0,
            'pending_deliveries' => 0,
            'pending_finished_goods' => 0,
            'material_cost' => 0,
            'labor_cost' => 0,
        ];

        if ($company) {
            $stats['inventory_quantity'] = (float) DB::table('inventory')
                ->where('company_id', $company->id)
                ->sum('quantity');

            $stats['open_orders'] = DB::table('orders')
                ->where('company_id', $company->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                ->count();

            $stats['shortage_requests'] = DB::table('supply_requests')
                ->where('company_id', $company->id)
                ->whereIn('status', ['shortage_pending', 'partially_supplied'])
                ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                ->count();

            $stats['active_productions'] = DB::table('productions')
                ->where('company_id', $company->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                ->count();

            if (Schema::hasTable('delivery_requests')) {
                $stats['pending_deliveries'] = DB::table('delivery_requests')
                    ->where('company_id', $company->id)
                    ->whereIn('status', ['pending', 'requested'])
                    ->when($fiscalYear && Schema::hasColumn('delivery_requests', 'fiscal_year_id'), fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                    ->count();
            }

            if (Schema::hasTable('finished_goods_receipts')) {
                $stats['pending_finished_goods'] = DB::table('finished_goods_receipts')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->count();
            }

            if ($fiscalYear && Schema::hasTable('inventory_consumption_costs')) {
                $stats['material_cost'] = (float) DB::table('inventory_consumption_costs')
                    ->where('company_id', $company->id)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->sum('total_cost');
            }

            if ($fiscalYear && Schema::hasTable('production_labor_costs')) {
                $stats['labor_cost'] = (float) DB::table('production_labor_costs')
                    ->where('company_id', $company->id)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->sum('total_cost');
            }
        }

        $permissions = [
            'company' => 'company.view',
            'customers' => 'customer.view',
            'orders' => 'order.view',
            'deliveries' => 'delivery_request.view',
            'personnel' => 'personnel.view',
            'users' => 'user.view',
            'roles' => 'role.view',
            'fiscal_years' => 'fiscal_year.view',
            'costing' => 'costing.report.view',
            'backups' => 'backup.view',
        ];

        $can = [];
        foreach ($permissions as $key => $permission) {
            $can[$key] = (bool) ($company && $user->hasCompanyPermission((int) $company->id, $permission));
        }

        $subscription = $company?->subscriptionEntitlement;
        $subscriptionActive = $subscription?->isActive() ?? false;
        $subscriptionStatus = $subscriptionActive
            ? 'فعال'
            : ($subscription ? 'منقضی یا غیرفعال' : 'ثبت نشده');

        return view('dashboard', compact(
            'user',
            'companies',
            'company',
            'fiscalYears',
            'fiscalYear',
            'stats',
            'subscription',
            'subscriptionActive',
            'subscriptionStatus',
            'can'
        ));
    }
}
