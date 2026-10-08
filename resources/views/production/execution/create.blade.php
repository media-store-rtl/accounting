@extends('layouts.app')
@section('content')
<div class="container" dir="rtl"><h1>ثبت اجرای عملیات تولید</h1>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>@endif
<form method="post" action="{{route('production.execution.store')}}" class="card p-4">@csrf
<label>عملیات</label><select name="production_operation_run_id" class="form-select mb-3" required>@foreach($runs as $r)<option value="{{$r->id}}">{{$r->number}} — {{$r->name}}</option>@endforeach</select>
<div class="row g-3"><div class="col-md-6"><label>کالای ورودی</label><select name="inputs[0][goods_id]" class="form-select">@foreach($goods as $g)<option value="{{$g->id}}">{{$g->code}} — {{$g->name}}</option>@endforeach</select></div><div class="col-md-6"><label>مقدار ورودی</label><input name="inputs[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control"></div>
<div class="col-md-6"><label>کالای خروجی</label><select name="outputs[0][goods_id]" class="form-select">@foreach($goods as $g)<option value="{{$g->id}}">{{$g->code}} — {{$g->name}}</option>@endforeach</select></div><div class="col-md-6"><label>مقدار خروجی</label><input name="outputs[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control"></div>
<div class="col-md-6"><label>کالای ضایعات</label><select name="scraps[0][goods_id]" class="form-select">@foreach($goods as $g)<option value="{{$g->id}}">{{$g->code}} — {{$g->name}}</option>@endforeach</select></div><div class="col-md-6"><label>مقدار ضایعات</label><input name="scraps[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control"></div>
<div class="col-md-6"><label>علت ضایعات</label><input name="scraps[0][reason]" class="form-control"></div><div class="col-md-6"><label>زمان پایان</label><input name="completed_at" type="datetime-local" class="form-control"></div>
<div class="col-12"><label>توضیحات</label><textarea name="notes" class="form-control"></textarea></div></div><button class="btn btn-primary mt-3">ثبت برای تأیید</button></form></div>
@endsection