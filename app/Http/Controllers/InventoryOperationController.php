<?php

namespace App\Http\Controllers;

use App\Models\Good;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Str;
use Illuminate\View\View;

class InventoryOperationController extends Controller
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
        $movements=InventoryMovement::with(['good','location','performer'])
            ->where('company_id',$companyId)->latest('occurred_at')->paginate(30);
        return view('definitions.goods.operations.index',compact('movements'));
    }

    public function create(): View
    {
        $companyId=$this->companyId();
        $goods=Good::where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $locations=Location::where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->orderBy('name')->get();
        return view('definitions.goods.operations.form',compact('goods','locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId=$this->companyId();
        $data=$request->validate([
            'good_id'=>['required','integer'],
            'movement_type'=>['required','in:receipt,issue,transfer,production_issue,finished_receipt,sales_delivery,adjustment'],
            'location_id'=>['required','integer'],
            'to_location_id'=>['nullable','integer'],
            'quantity'=>['required','numeric','gt:0'],
            'adjustment_sign'=>['nullable','in:increase,decrease'],
            'reference_type'=>['nullable','string','max:80'],
            'reference_id'=>['nullable','integer'],
            'occurred_at'=>['required','date'],
            'notes'=>['nullable','string'],
        ]);
        if($data['movement_type']==='adjustment') {
            abort_unless(request()->user()->hasPermission('goods.operation.adjust'),403,'مجوز اصلاح موجودی را ندارید.');
            abort_unless(!empty($data['adjustment_sign']),422,'جهت اصلاح موجودی الزامی است.');
        }

        $good=Good::where('company_id',$companyId)->where('is_active',true)->findOrFail($data['good_id']);
        $source=Location::where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->findOrFail($data['location_id']);
        $target=$data['to_location_id'] ? Location::where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->findOrFail($data['to_location_id']) : null;
        if($data['movement_type']==='transfer') abort_if(!$target || $target->id===$source->id,422,'مقصد انتقال معتبر نیست.');
        elseif($target) abort(422,'مقصد فقط برای انتقال مجاز است.');

        DB::transaction(function() use($data,$good,$source,$target,$companyId){
            $qty=(float)$data['quantity'];
            $group=$data['movement_type']==='transfer' ? (string)Str::uuid() : null;

            if(in_array($data['movement_type'],['issue','production_issue','sales_delivery'],true)) {
                $this->apply($companyId,$good->id,$source->id,-$qty);
                $this->record($companyId,$good->id,$source->id,-$qty,$data['movement_type'],$data,$group);
            } elseif($data['movement_type']==='adjustment') {
                $signed=($data['adjustment_sign']==='decrease' ? -1 : 1)*$qty;
                $this->apply($companyId,$good->id,$source->id,$signed);
                $this->record($companyId,$good->id,$source->id,$signed,'adjustment',$data,null);
            } elseif($data['movement_type']==='transfer') {
                $this->apply($companyId,$good->id,$source->id,-$qty);
                $this->record($companyId,$good->id,$source->id,-$qty,'transfer',$data,$group);
                $this->apply($companyId,$good->id,$target->id,$qty);
                $this->record($companyId,$good->id,$target->id,$qty,'transfer',$data,$group);
            } else {
                $this->apply($companyId,$good->id,$source->id,$qty);
                $this->record($companyId,$good->id,$source->id,$qty,$data['movement_type'],$data,null);
            }
        });

        return redirect()->route('definitions.goods.operations.index')->with('success','عملیات انبار ثبت شد.');
    }

    private function apply(int $companyId,int $goodId,int $locationId,float $delta): void
    {
        $balance=InventoryBalance::where('company_id',$companyId)->where('goods_id',$goodId)->where('location_id',$locationId)->lockForUpdate()->first();
        if(!$balance) {
            InventoryBalance::create(['company_id'=>$companyId,'goods_id'=>$goodId,'location_id'=>$locationId,'quantity'=>0,'reorder_point'=>0]);
            $balance=InventoryBalance::where('company_id',$companyId)->where('goods_id',$goodId)->where('location_id',$locationId)->lockForUpdate()->firstOrFail();
        }
        $new=(float)$balance->quantity+$delta;
        abort_if($new < 0,422,'موجودی کافی نیست.');
        $balance->update(['quantity'=>$new]);
    }

    private function record(int $companyId,int $goodId,int $locationId,float $qty,string $type,array $data,?string $group): void
    {
        InventoryMovement::create([
            'company_id'=>$companyId,'goods_id'=>$goodId,'location_id'=>$locationId,'quantity'=>$qty,
            'movement_type'=>$type,'reference_type'=>$data['reference_type']??null,'reference_id'=>$data['reference_id']??null,
            'performed_by'=>request()->user()->id,'transfer_reference'=>$group,'occurred_at'=>$data['occurred_at'],
            'metadata'=>['notes'=>$data['notes']??null],
        ]);
    }
}
