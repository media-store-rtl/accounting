<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Models\GoodsCategory;
use App\Models\GoodsUnit;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GoodController extends Controller
{
    private function companyId(): int
    {
        $id=(int)session('company_id');
        abort_unless($id && request()->user()->companies()->whereKey($id)->where('companies.is_active',true)->exists(),403);
        return $id;
    }

    public function index(): View
    {
        $companyId=$this->companyId();
        $goods=Good::with('category')->where('company_id',$companyId)->orderBy('name')->paginate(25);
        return view('definitions.goods.index',compact('goods'));
    }

    public function create(): View { return $this->form(null); }

    public function store(Request $request): RedirectResponse
    {
        $companyId=$this->companyId(); $data=$this->validated($request,$companyId);
        DB::transaction(function() use($data,$companyId){
            $supplierIds=$data['supplier_ids']??[]; $baseUnitId=$data['base_unit_id']; unset($data['supplier_ids'],$data['base_unit_id']);
            $data['company_id']=$companyId;
            $good=Good::create($data);
            $good->suppliers()->sync($supplierIds);
            GoodsUnit::create(['goods_id'=>$good->id,'unit_id'=>$baseUnitId,'conversion_factor'=>1,'is_base'=>true]);
        });
        return redirect()->route('definitions.goods.index')->with('success','کالا/خدمت ثبت شد.');
    }

    public function edit(Good $good): View { $this->assertOwner($good); return $this->form($good); }

    public function update(Request $request, Good $good): RedirectResponse
    {
        $this->assertOwner($good); $data=$this->validated($request,$good->company_id);
        DB::transaction(function() use($data,$good){
            $supplierIds=$data['supplier_ids']??[]; $baseUnitId=$data['base_unit_id']; unset($data['supplier_ids'],$data['base_unit_id']);
            $good->update($data); $good->suppliers()->sync($supplierIds);
            GoodsUnit::where('goods_id',$good->id)->update(['is_base'=>false]);
            GoodsUnit::updateOrCreate(['goods_id'=>$good->id,'unit_id'=>$baseUnitId],['conversion_factor'=>1,'is_base'=>true]);
        });
        return redirect()->route('definitions.goods.index')->with('success','کالا/خدمت ویرایش شد.');
    }

    public function activate(Good $good): RedirectResponse { $this->assertOwner($good); $good->update(['is_active'=>true]); return back(); }
    public function deactivate(Good $good): RedirectResponse { $this->assertOwner($good); $good->update(['is_active'=>false]); return back(); }

    private function form(?Good $good): View
    {
        $companyId=$this->companyId();
        $categories=GoodsCategory::where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $units=Unit::where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $suppliers=Supplier::where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $selectedSuppliers=$good?->suppliers()->pluck('suppliers.id')->all() ?? [];
        $baseUnitId=$good?->units()->where('is_base',true)->value('unit_id');
        return view('definitions.goods.form',compact('good','categories','units','suppliers','selectedSuppliers','baseUnitId'));
    }

    private function validated(Request $request,int $companyId): array
    {
        $data=$request->validate([
            'category_id'=>['required','integer',Rule::exists('goods_categories','id')->where(fn($q)=>$q->where('company_id',$companyId))],
            'code'=>['required','string','max:100',Rule::unique('goods','code')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($request->route('good')?->id)],
            'name'=>['required','string','max:255'],
            'item_type'=>['required',Rule::in(['product','service'])],
            'product_type'=>['nullable',Rule::in(['raw_material','semi_finished','finished'])],
            'purchasable'=>['sometimes','boolean'],'producible'=>['sometimes','boolean'],'sellable'=>['sometimes','boolean'],
            'description'=>['nullable','string'],
            'base_unit_id'=>['required','integer',Rule::exists('units','id')->where(fn($q)=>$q->where('company_id',$companyId))],
            'supplier_ids'=>['array'],
            'supplier_ids.*'=>[Rule::exists('suppliers','id')->where(fn($q)=>$q->where('company_id',$companyId))],
        ]);
        if($data['item_type']==='service') $data['product_type']=null;
        $data['purchasable']=(bool)($data['purchasable']??false);
        $data['producible']=(bool)($data['producible']??false);
        $data['sellable']=(bool)($data['sellable']??false);
        return $data;
    }

    private function assertOwner(Good $good): void { abort_unless($good->company_id===$this->companyId(),404); }
}
