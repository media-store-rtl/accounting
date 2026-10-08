<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>گزارش بهای تمام‌شده</title>
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}*{box-sizing:border-box}body{margin:0;background:#07111f}.page{min-height:100vh;padding:28px}.head{display:flex;justify-content:space-between;align-items:end;margin-bottom:18px}.head h1{margin:5px 0;font-size:25px}.muted{color:#71879d;font-size:11px}.filters,.panel,.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:18px}.filters{margin-bottom:16px}.filters form{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.filters label{display:block;color:#7890a5;font-size:9px;margin-bottom:6px}.filters select,.filters button{width:100%;background:#0d2235;color:#dcebf6;border:1px solid #29465e;border-radius:10px;padding:10px;font-family:inherit;font-size:10px}.filters button{cursor:pointer;background:#153b52}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}.card small{color:#71879d;font-size:9px}.card b{display:block;font-size:19px;margin-top:8px}.panel{margin-bottom:16px}.warn{background:#2c2413;color:#e5c87c;border:1px solid #5c4a25;border-radius:10px;padding:11px;font-size:10px;margin-bottom:12px}.table-wrap{overflow:auto}.table{width:100%;border-collapse:collapse;min-width:900px}.table th,.table td{padding:11px 9px;border-bottom:1px solid #183047;text-align:right;font-size:10px}.table th{color:#71879d;font-weight:500}.table td{color:#dcebf6}.empty{text-align:center;padding:35px;color:#71879d;border:1px dashed #29455d;border-radius:12px;font-size:10px}@media(max-width:800px){.filters form{grid-template-columns:1fr 1fr}.cards{grid-template-columns:1fr 1fr}.page{padding:16px}}@media(max-width:500px){.filters form,.cards{grid-template-columns:1fr}.head{display:block}}
</style>
</head>
<body><div class="page">
<div class="head"><div><div class="muted">REPORTS / COSTING</div><h1>گزارش بهای تمام‌شده</h1><div class="muted">هزینه واقعی ثبت‌شده در سطح سفارش و محصول.</div></div><a href="/dashboard" style="color:#8eb7d3;text-decoration:none;font-size:10px">← داشبورد</a></div>
<div class="filters"><form method="GET" action="{{ route('reports.costing') }}">
<div><label>سال مالی</label><select name="fiscal_year_id"><option value="">همه</option>@foreach($fiscalYears as $fy)<option value="{{ $fy->id }}" @selected((int)request('fiscal_year_id')===(int)$fy->id)>{{ $fy->name }}</option>@endforeach</select></div>
<div><label>سفارش</label><select name="order_id"><option value="">همه</option>@foreach($orders as $order)<option value="{{ $order->id }}" @selected((int)request('order_id')===(int)$order->id)>{{ $order->number }}</option>@endforeach</select></div>
<div><label>محصول / کالا</label><select name="goods_id"><option value="">همه</option>@foreach($goods as $good)<option value="{{ $good->id }}" @selected((int)request('goods_id')===(int)$good->id)>{{ $good->code }} — {{ $good->name }}</option>@endforeach</select></div>
<div style="display:flex;align-items:end"><button type="submit">نمایش گزارش</button></div>
</form></div>
<div class="cards" style="grid-template-columns:repeat(5,1fr)">
<div class="card"><small>مواد مصرف‌شده</small><b>{{ number_format($report['totals']['material_cost'],4) }}</b></div>
<div class="card"><small>دستمزد تأییدشده</small><b>{{ number_format($report['totals']['labor_cost'],4) }}</b></div>
<div class="card"><small>هزینه مستقیم خرید</small><b>{{ number_format($report['totals']['direct_cost'] ?? 0,4) }}</b></div>
<div class="card"><small>بهای تمام‌شده</small><b>{{ number_format($report['totals']['total_cost'],4) }}</b></div>
<div class="card"><small>مصرف بدون ارزش‌گذاری</small><b>{{ number_format($report['totals']['unvalued_material_quantity'],4) }}</b></div>
</div>
@if(!empty($report['valuation_methods']))<div class="panel"><span class="muted">روش ارزش‌گذاری:</span> {{ implode('، ',$report['valuation_methods']) }}</div>@endif
@if($report['totals']['unvalued_material_quantity']>0)<div class="warn">بخشی از خروج مواد هنوز ارزش‌گذاری نشده است؛ این مقدار عمداً در مبلغ بهای تمام‌شده وارد نشده است.</div>@endif
<div class="panel"><div class="table-wrap">
@if(empty($report['rows']))<div class="empty">برای فیلتر انتخاب‌شده داده هزینه‌ای ثبت نشده است.</div>@else
<table class="table"><thead><tr><th>سفارش</th><th>محصول</th><th>مواد</th><th>دستمزد</th><th>هزینه مستقیم</th><th>ضایعات</th><th>جمع</th><th>روش</th><th>مصرف بدون ارزش‌گذاری</th></tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row['order_id'] ?? 'بدون سفارش' }}</td><td>{{ $row['goods_code'] }} — {{ $row['goods_name'] }}</td><td>{{ number_format($row['material_cost'],4) }}</td><td>{{ number_format($row['labor_cost'],4) }}</td><td>{{ number_format($row['direct_cost'] ?? 0,4) }}</td><td>{{ number_format($row['scrap_cost'] ?? 0,4) }}</td><td><strong>{{ number_format($row['total_cost'],4) }}</strong></td><td>{{ implode('، ',$row['valuation_methods']) ?: '—' }}</td><td>{{ number_format($row['unvalued_material_quantity'],4) }}</td></tr>@endforeach
</tbody></table>@endif
</div></div>
</div></body></html>
