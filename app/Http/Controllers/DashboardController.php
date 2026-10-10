<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

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

        $goods = collect();
        $goodsCount = 0;

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

            {
                $stats['pending_deliveries'] = DB::table('delivery_requests')
                    ->where('company_id', $company->id)
                    ->whereIn('status', ['pending', 'requested'])
                    ->count();
            }

            {
                $stats['pending_finished_goods'] = DB::table('finished_goods_receipts')
                    ->where('company_id', $company->id)
                    ->where('status', 'pending')
                    ->count();
            }

            if ($fiscalYear) {
                $stats['material_cost'] = (float) DB::table('inventory_consumption_costs')
                    ->where('company_id', $company->id)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->sum('total_cost');
            }

            if ($fiscalYear) {
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
            'goods' => 'goods.view',
        ];

        $can = [];
        foreach ($permissions as $key => $permission) {
            $can[$key] = (bool) ($company && $user->hasCompanyPermission((int) $company->id, $permission));
        }

        if ($company && $user->hasCompanyPermission((int) $company->id, 'goods.view')) {
            $goodsQuery = DB::table('goods')->where('company_id', $company->id)->where('is_active', true);
            $goodsCount = (clone $goodsQuery)->count();
            $goods = $goodsQuery->orderByDesc('id')->limit(8)->get(['id', 'code', 'name']);
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
            'goods',
            'goodsCount',
            'subscription',
            'subscriptionActive',
            'subscriptionStatus',
            'can'
        ));
    }
}
