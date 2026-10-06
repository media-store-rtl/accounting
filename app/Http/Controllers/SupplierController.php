<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    private function companyId(): int
    {
        $id = (int) session('company_id');
        abort_unless($id && request()->user()->companies()->whereKey($id)->where('companies.is_active', true)->exists(), 403);
        return $id;
    }

    public function index(): View
    {
        $companyId = $this->companyId();
        $suppliers = Supplier::where('company_id', $companyId)->orderBy('name')->paginate(25);
        return view('definitions.suppliers.index', compact('suppliers'));
    }

    public function create(): View { return view('definitions.suppliers.form', ['supplier' => null]); }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId();
        $data = $request->validate([
            'code' => ['required','string','max:50',Rule::unique('suppliers','code')->where(fn($q)=>$q->where('company_id',$companyId))],
            'name' => ['required','string','max:255'],
            'national_id' => ['nullable','string','max:50'],
            'phone' => ['nullable','string','max:50'],
            'mobile' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'province' => ['nullable','string','max:100'],
            'city' => ['nullable','string','max:100'],
            'address' => ['nullable','string'],
            'postal_code' => ['nullable','string','max:20'],
            'person_type' => ['required',Rule::in(['person','company'])],
        ]);
        $extra = collect($data)->only(['mobile','website','province','city','postal_code','person_type'])->all();
        foreach (array_keys($extra) as $key) unset($data[$key]);
        $data['company_id'] = $companyId;
        $data['settings'] = $extra;
        Supplier::create($data);
        return redirect()->route('definitions.suppliers.index')->with('success','تأمین‌کننده ثبت شد.');
    }

    public function edit(Supplier $supplier): View
    {
        $this->assertOwner($supplier);
        return view('definitions.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->assertOwner($supplier);
        $companyId = $this->companyId();
        $data = $request->validate([
            'code' => ['required','string','max:50',Rule::unique('suppliers','code')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($supplier->id)],
            'name' => ['required','string','max:255'],
            'national_id' => ['nullable','string','max:50'],
            'phone' => ['nullable','string','max:50'],
            'mobile' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'website' => ['nullable','url','max:255'],
            'province' => ['nullable','string','max:100'],
            'city' => ['nullable','string','max:100'],
            'address' => ['nullable','string'],
            'postal_code' => ['nullable','string','max:20'],
            'person_type' => ['required',Rule::in(['person','company'])],
        ]);
        $extra = collect($data)->only(['mobile','website','province','city','postal_code','person_type'])->all();
        foreach (array_keys($extra) as $key) unset($data[$key]);
        $data['settings'] = array_merge($supplier->settings ?? [], $extra);
        $supplier->update($data);
        return redirect()->route('definitions.suppliers.index')->with('success','تأمین‌کننده ویرایش شد.');
    }

    public function activate(Supplier $supplier): RedirectResponse { $this->assertOwner($supplier); $supplier->update(['is_active'=>true]); return back(); }
    public function deactivate(Supplier $supplier): RedirectResponse { $this->assertOwner($supplier); $supplier->update(['is_active'=>false]); return back(); }

    private function assertOwner(Supplier $supplier): void
    {
        abort_unless($supplier->company_id === $this->companyId(), 404);
    }
}
