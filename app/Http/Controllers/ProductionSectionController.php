<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Personnel;
use App\Models\ProductionSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductionSectionController extends Controller
{
    private function companyId(): int
    {
        $id=(int)session('company_id');
        abort_unless($id && request()->user()->companies()->whereKey($id)->where('companies.is_active',true)->exists(),403);
        return $id;
    }

    public function index(): View
    {
        $sections=ProductionSection::with(['location','supervisor','personnel'])
            ->whereHas('location',fn($q)=>$q->where('company_id',$this->companyId()))
            ->orderByDesc('id')->paginate(25);
        return view('definitions.production-sections.index',compact('sections'));
    }

    public function create(): View
    {
        $personnel=Personnel::where('account_id',request()->user()->account_id)->where('is_active',true)->orderBy('name')->get();
        return view('definitions.production-sections.form',['section'=>null,'personnel'=>$personnel]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId=$this->companyId(); $data=$this->validateData($request);
        $this->assertPersonnel($data['supervisor_personnel_id']??null,$data['personnel_ids']??[]);
        DB::transaction(function() use($data,$companyId){
            $location=Location::create(['company_id'=>$companyId,'code'=>$data['code'],'name'=>$data['name'],'type'=>'production_section','is_active'=>true]);
            $section=ProductionSection::create(['location_id'=>$location->id,'supervisor_personnel_id'=>$data['supervisor_personnel_id']??null]);
            $section->personnel()->sync($data['personnel_ids']??[]);
        });
        return redirect()->route('definitions.production-sections.index')->with('success','قسمت تولید ثبت شد.');
    }

    public function edit(ProductionSection $section): View
    {
        $this->assertOwner($section); $section->load(['location','personnel']);
        $personnel=Personnel::where('account_id',request()->user()->account_id)->where('is_active',true)->orderBy('name')->get();
        return view('definitions.production-sections.form',compact('section','personnel'));
    }

    public function update(Request $request, ProductionSection $section): RedirectResponse
    {
        $this->assertOwner($section); $data=$this->validateData($request);
        $this->assertPersonnel($data['supervisor_personnel_id']??null,$data['personnel_ids']??[]);
        $section->load('location');
        DB::transaction(function() use($data,$section){
            $section->location->update(['code'=>$data['code'],'name'=>$data['name']]);
            $section->update(['supervisor_personnel_id'=>$data['supervisor_personnel_id']??null]);
            $section->personnel()->sync($data['personnel_ids']??[]);
        });
        return redirect()->route('definitions.production-sections.index')->with('success','قسمت تولید ویرایش شد.');
    }

    public function activate(ProductionSection $section): RedirectResponse { $this->assertOwner($section); $section->load('location')->location->update(['is_active'=>true]); return back(); }
    public function deactivate(ProductionSection $section): RedirectResponse { $this->assertOwner($section); $section->load('location')->location->update(['is_active'=>false]); return back(); }

    private function validateData(Request $request): array
    {
        $companyId=$this->companyId();
        return $request->validate([
            'code'=>['required','string','max:50',Rule::unique('locations','code')->where(fn($q)=>$q->where('company_id',$companyId))->ignore($request->route('section')?->location_id)],
            'name'=>['required','string','max:255'],
            'supervisor_personnel_id'=>['nullable','integer'],
            'personnel_ids'=>['array'],
            'personnel_ids.*'=>['integer'],
        ]);
    }

    private function assertPersonnel(?int $supervisorId,array $personnelIds): void
    {
        $ids=array_values(array_filter(array_map('intval',$personnelIds)));
        if($supervisorId) $ids[]=$supervisorId;
        if(!$ids) return;
        $count=Personnel::where('account_id',request()->user()->account_id)->whereIn('id',array_unique($ids))->where('is_active',true)->count();
        abort_unless($count===count(array_unique($ids)),422,'پرسنل انتخاب‌شده معتبر نیست.');
    }

    private function assertOwner(ProductionSection $section): void
    {
        abort_unless($section->location()->where('company_id',$this->companyId())->exists(),404);
    }
}
