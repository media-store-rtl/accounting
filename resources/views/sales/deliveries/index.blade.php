@extends('layouts.dashboard-shell')

@section('title', 'تحویل و ارسال')

@section('content')
<div class="page-heading"><div><h1>تحویل و ارسال</h1><p>مدیریت خروج سفارش از انبار و تحویل به مشتری</p></div><a class="btn" href="{{ route('sales.orders.index') }}">مشاهده سفارش‌های فروش</a></div>
<div class="panel"><div class="table-wrap"><table><thead><tr><th>سفارش</th><th>مشتری</th><th>زمان تحویل</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($deliveries as $d)
<tr><td>{{ $d->order_number }}</td><td>{{ $d->customer_name }}</td><td>{{ $d->scheduled_at }}</td><td>@if($d->status === 'pending')<span class="status inactive">در انتظار خروج</span>@elseif($d->status === 'issued')<span class="status active">خارج‌شده از انبار</span>@elseif($d->status === 'delivered')<span class="status active">تحویل‌شده</span>@else<span class="status">{{ $d->status }}</span>@endif</td><td>@if($d->status === 'pending')<button class="btn primary" type="button" onclick="issue({{ $d->id }})">خروج از انبار</button>@elseif($d->status === 'issued')<button class="btn primary" type="button" onclick="handover({{ $d->id }})">تحویل به مشتری</button>@else<span style="color:#71879d">—</span>@endif</td></tr>
@empty
<tr><td colspan="5" style="text-align:center;color:#71879d;padding:28px">درخواستی برای تحویل ثبت نشده است.</td></tr>
@endforelse
</tbody></table></div></div>
<script>const token='{{ csrf_token() }}';async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(data)});const j=await r.json();if(!r.ok)throw Error(j.message||'خطا');return j}async function issue(id){if(!confirm('خروج این سفارش از انبار ثبت شود؟'))return;try{await post('/api/delivery-requests/'+id+'/issue');location.reload()}catch(e){alert(e.message)}}async function handover(id){const received=prompt('نام تحویل‌گیرنده را وارد کنید');if(!received)return;try{await post('/api/delivery-requests/'+id+'/handover',{received_by:received});location.reload()}catch(e){alert(e.message)}}</script>
@endsection
