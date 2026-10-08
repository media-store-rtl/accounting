@extends('layouts.app')
@section('content')
<div class="container py-3" dir="rtl">
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>@endif
<h1 class="h3">موجودی انبار</h1><div class="table-responsive"><table class="table align-middle"><thead><tr><th>کد</th><th>کالا</th><th>انبار</th><th>موجودی</th><th>نقطه سفارش</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)<tr class="{{(float)($row->reorder_point??0)>0&&(float)$row->quantity<=(float)$row->reorder_point?'table-warning':''}}"><td>{{$row->goods_code}}</td><td>{{$row->goods_name}}</td><td>{{$row->location_name}}</td><td>{{$row->quantity}}</td><td>{{$row->reorder_point??0}}</td><td>
@if(auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.reorder_point.manage'))<form method="post" action="{{route('inventory.reorder-point',$row->id)}}" class="d-inline-flex gap-1">@csrf<input name="reorder_point" type="number" min="0" step="0.0001" value="{{$row->reorder_point??0}}" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-secondary">نقطه سفارش</button></form>@endif
@if(auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.adjust'))<form method="post" action="{{route('inventory.adjust',$row->id)}}" class="d-inline-flex gap-1">@csrf<input name="quantity" type="number" min="0" step="0.0001" value="{{$row->quantity}}" class="form-control form-control-sm"><input name="reason" required placeholder="علت" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-primary">اصلاح</button></form>@endif
</td></tr>@empty<tr><td colspan="6" class="text-center">موجودی ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>{{$rows->links()}}</div>
@endsection