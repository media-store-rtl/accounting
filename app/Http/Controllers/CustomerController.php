<?php

namespace App\\Http\\Controllers;

use App\\Support\\CompanyAuthorization;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'customer.view');
        $customers = DB::table('customers')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->paginate(20);
        $canEditCustomers = $request->user()->hasCompanyPermission($companyId, 'customer.update');

        return view('sales.customers.index', compact('customers', 'canEditCustomers'));
    }

    public function create(Request $request)
    {
        CompanyAuthorization::authorize($request, 'customer.create');

        return view('sales.customers.create');
    }

    public function store(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'customer.create');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        DB::table('customers')->insert(array_merge($data, [
            'company_id' => $companyId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('sales.customers.index')->with('success', 'مشتری با موفقیت ثبت شد.');
    }

    public function edit(Request $request, int $id)
    {
        $companyId = CompanyAuthorization::authorize($request, 'customer.update');
        $customer = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        abort_unless($customer, 404);

        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, int $id)
    {
        $companyId = CompanyAuthorization::authorize($request, 'customer.update');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        $updated = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()]));

        abort_unless($updated || DB::table('customers')->where('company_id', $companyId)->where('id', $id)->exists(), 404);

        return redirect()->route('sales.customers.index')->with('success', 'اطلاعات مشتری با موفقیت ویرایش شد.');
    }
}
