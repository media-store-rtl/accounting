@extends('layouts.app')
@section('content')
<div class="container" dir="rtl">
<div class="d-flex justify-content-between align-items-center mb-3"><h1>اجرای عملیات تولید</h1><a class="btn btn-primary" href="{{route('production.execution.create')}}">ثبت عملیات</a></div>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<div class="table-responsive"><table class="table"><thead><tr><th>تولید</th><th>مرحله</th><th>عملیات</th><th>ورودی</th><th>خروجی</th><th>ضایعات</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>
@forelse($rows as $x)<tr><td>{{$x->production_number}}</td><td>{{$x->stage_name}}</td><td>{{$x->operation_name}}</td><td>{{$x->input_quantity??'—'}}</td><td>{{$x->output_quantity??'—'}}</td><td>{{$x->rejected_quantity??0}}</td><td>{{$x->status}}</td><td>@if($x->status==='pending_review')<form method="post" action="{{route('production.execution.review',$x->id)}}" class="d-inline">@csrf<input type="hidden" name="approve" value="1"><button class="btn btn-sm btn-success">تأیید</button></form><form method="post" action="{{route('production.execution.review',$x->id)}}" class="d-inline">@csrf<input type="hidden" name="approve" value="0"><input type="hidden" name="reason" value="رد توسط سرپرست"><button class="btn btn-sm btn-outline-danger">رد</button></form>@else—@endif</td></tr>@empty<tr><td colspan="8" class="text-center py-5">عملیاتی ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>{{$rows->links()}}</div>
@endsection