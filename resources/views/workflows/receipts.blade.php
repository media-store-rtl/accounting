@extends('layouts.app')
@section('content')
<div class="container" dir="rtl">
<h1>رسید خرید</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="post" action="{{ route('workflows.receipts.store') }}" class="card p-4">@csrf
<label>خرید</label><select name="purchase_id" class="form-select">@foreach($purchases as $p)<option value="{{ $p->id }}">{{ $p->id }}</option>@endforeach</select>
<label class="mt-2">انبار</label><select name="warehouse_location_id" class="form-select">@foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select>
<label class="mt-2">تاریخ دریافت</label><input name="received_at" type="datetime-local" class="form-control" required>
<label class="mt-2">کالا</label><select name="items[0][goods_id]" class="form-select">@foreach($goods as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select>
<label class="mt-2">مقدار</label><input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" class="form-control" required>
<button class="btn btn-primary mt-3">ثبت</button></form>
<table class="table mt-4"><thead><tr><th>شناسه</th><th>خرید</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->purchase_id }}</td><td>{{ $row->status }}</td><td>@if($row->status==='pending')<form method="post" action="{{ route('workflows.receipts.approve',$row->id) }}">@csrf<button class="btn btn-sm btn-success">تأیید</button></form>@endif</td></tr>@empty<tr><td colspan="4">رسیدی وجود ندارد.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}
</div>
@endsection