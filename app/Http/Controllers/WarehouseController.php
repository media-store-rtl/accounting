<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    private function companyId(): int
    {
        $id=(int)session('company_id');
        abort_unless($id && request()->user()->companies()->whereKey($id)->where('companies.is_active',true)->exists(),403);
        return $id;
    }

    public function index(): View
    {
        $warehouses=Location::where('company_id',$this->companyId())->where('type','warehouse')->orderBy('name')->paginate(25);
        return view('definitions.warehouses.index',compact('warehouses'));
    }

    public function create(): View { return view('definitions.warehouses.form',['warehouse'=>null]); }

    public function store(Request $request): RedirectResponse
    {
        $companyId=$this->companyId();
        $data=$request->validate([
            'code'=>['required','string','max:50',Rule::unique('locations','code')->where(fn($q)=>$q->where('company_id',$companyId))],
            'name'=>['required','string','max:255'],
        ]);
        $data['company_id']=$companyId; $data['type']='warehouse'; $data['is_active']=true;
        Location::create($data);
        return redirect()->route('definitions.warehouses.index')->with('success','انبار ثبت شد.');
    }

    public function edit(Location $warehouse): View { $this->assertWarehouse($warehouse); return view('definitions.warehouses.form',compact('warehouse')); }

    public function update(Request $request, Location $warehouse): RedirectResponse
    {
        $this->assertWarehouse($warehouse); $companyId=$this->companyId();
        $data=$request->validate([
            'code'=>['required','string','max:50',Rule::unique('locations','code')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($warehouse->id)],
            'name'=>['required','string','max:255'],
        ]);
        $warehouse->update($data);
        return redirect()->route('definitions.warehouses.index')->with('success','انبار ویرایش شد.');
    }

    public function activate(Location $warehouse): RedirectResponse { $this->assertWarehouse($warehouse); $warehouse->update(['is_active'=>true]); return back(); }
    public function deactivate(Location $warehouse): RedirectResponse { $this->assertWarehouse($warehouse); $warehouse->update(['is_active'=>false]); return back(); }

    private function assertWarehouse(Location $warehouse): void
    {
        abort_unless($warehouse->company_id===$this->companyId() && $warehouse->type==='warehouse',404);
    }
}
