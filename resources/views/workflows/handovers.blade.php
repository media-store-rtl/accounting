@extends('layouts.app')
@section('content')
<div class="container" dir="rtl"><h1>تحویل مواد به تولید</h1>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<form method="post" action="{{route('workflows.handovers.store')}}" class="card p-4 mb-4">@csrf
<div class="row g-3"><div class="col-md-6"><label>درخواست تأمین</label><select name="supply_request_id" class="form-select" required>@foreach($requests as $x)<option value="{{$x->id}}">{{$x->id}} / {{$x->status}}</option>@endforeach</select></div>
<div class="col-md-6"><label>انبار</label><select name="warehouse_location_id" class="form-select" required>@foreach($locations as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div class="col-md-6"><label>تحویل‌دهنده</label><select name="delivered_by_user_id" class="form-select" required>@foreach($users as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div class="col-md-6"><label>تحویل‌گیرنده</label><select name="received_by_user_id" class="form-select" required>@foreach($users as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div class="col-md-6"><label>زمان</label><input name="handed_over_at" type="datetime-local" class="form-control" required></div>
<div class="col-md-6"><label>کالا</label><select name="items[0][goods_id]" class="form-select" required>@foreach($goods as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div class="col-md-6"><label>مقدار</label><input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control" required></div></div>
<button class="btn btn-primary mt-3">ثبت تحویل</button></form>
<table class="table"><thead><tr><th>شناسه</th><th>درخواست</th><th>وضعیت</th><th>زمان</th></tr></thead><tbody>@forelse($rows as $x)<tr><td>{{$x->id}}</td><td>{{$x->supply_request_id}}</td><td>{{$x->status}}</td><td>{{$x->handed_over_at}}</td></tr>@empty<tr><td colspan="4">تحویلی وجود ندارد.</td></tr>@endforelse</tbody></table>{{$rows->links()}}</div>
@endsection