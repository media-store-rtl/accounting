@extends('layouts.app')
@section('content')
<div class="container" dir="rtl"><h1>تحویل مواد</h1>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<form method="post" action="{{route('workflows.handovers.store')}}" class="card p-4 mb-4">@csrf
<select name="supply_request_id" class="form-select mb-2" required>@foreach($requests as $x)<option value="{{$x->id}}>{{$x->id}}</option>@endforeach</select>
<select name="warehouse_location_id" class="form-select mb-2" required>@foreach($locations as $x)<option value="{{$x->id}}>{{$x->name}}</option>@endforeach</select>
<select name="delivered_by_user_id" class="form-select mb-2" required>@foreach($users as $x)<option value="{{$x->id}}>{{$x->name}}</option>@endforeach</select>
<select name="received_by_user_id" class="form-select mb-2" required>@foreach($users as $x)<option value="{{$x->id}}>{{$x->name}}</option>@endforeach</select>
<input name="handed_over_at" type="datetime-local" class="form-control mb-2" required>
<select name="items[0][goods_id]" class="form-select mb-2" required>@foreach($goods as $x)<option value="{{$x->id}}>{{$x->name}}</option>@endforeach</select>
<input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control mb-2" required>
<button class="btn btn-primary">ثبت</button></form>
<table class="table"><thead><tr><th>شناسه</th><th>درخواست</th><th>وضعیت</th></tr></thead><tbody>@forelse($rows as $x)<tr><td>{{$x->id}}</td><td>{{$x->supply_request_id}}</td><td>{{$x->status}}</td></tr>@empty<tr><td colspan="3">موردی وجود ندارد.</td></tr>@endforelse</tbody></table>{{$rows->links()}}</div>
@endsection