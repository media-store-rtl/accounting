@extends('layouts.app')
@section('content')
<div class="container py-3" dir="rtl"><h1 class="h3 mb-4">مرکز اعلان‌ها</h1>
<div class="list-group">@forelse($rows as $n)@php $data=json_decode($n->data,true)?:[]; @endphp
<div class="list-group-item {{ $n->read_at?'':'fw-semibold' }}"><div class="d-flex justify-content-between"><span>{{ $data['event'] ?? $n->type }}</span><small>{{ $n->created_at }}</small></div><div class="small text-muted mt-1">@foreach($data as $k=>$v)@if($k!=='event'){{ $k }}: {{ is_scalar($v)?$v:json_encode($v) }} @endif @endforeach</div>@if(!$n->read_at)<form method="post" action="{{route('notifications.read',$n->id)}}" class="mt-2">@csrf<button class="btn btn-sm btn-outline-primary">خوانده شد</button></form>@endif</div>
@empty<div class="alert alert-secondary">اعلانی وجود ندارد.</div>@endforelse</div>{{$rows->links()}}</div>
@endsection