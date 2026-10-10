@extends('layouts.dashboard-shell')

@section('title', 'خرید')

@section('content')
<div class="page-heading">
    <div>
        <h1>خریدها</h1>
        <p>ثبت خرید از تأمین‌کنندگان و پیگیری وضعیت سفارش‌های خرید</p>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="panel" style="margin-bottom:18px">
    <h2 style="font-size:15px;margin:0 0 16px">ثبت خرید جدید</h2>
    <form method="POST" action="{{ route('workflows.purchasing.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">سال مالی
                <select name="fiscal_year_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($years as $y)<option value="{{ $y->id }}" @selected((string)old('fiscal_year_id')===(string)$y->id)>{{ $y->year ?? $y->id }}</option>@endforeach
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تأمین‌کننده
                <select name="supplier_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)old('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">درخواست تأمین
                <select name="supply_request_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($requests as $s)<option value="{{ $s->id }}" @selected((string)old('supply_request_id')===(string)$s->id)>درخواست #{{ $s->id }}</option>@endforeach
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا
                <select name="items[0][goods_id]" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.0.goods_id')===(string)$g->id)>{{ $g->name }}</option>@endforeach
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار
                <input name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">قیمت واحد
                <input name="items[0][unit_price]" type="number" min="0" step="0.0001" value="{{ old('items.0.unit_price') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ خرید
                <input name="purchased_at" type="date" value="{{ old('purchased_at') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark">
            </label>
        </div>
        <div style="margin-top:18px"><button class="btn primary" type="submit">ثبت خرید</button></div>
    </form>
</div>

<div class="panel">
    <div style="margin-bottom:14px"><h2 style="font-size:15px;margin:0 0 5px">خریدهای ثبت‌شده</h2><p style="font-size:11px;color:#8198ac;margin:0">وضعیت و مبلغ خریدها</p></div>
    <div class="table-wrap"><table><thead><tr><th>شناسه</th><th>مبلغ</th><th>وضعیت</th></tr></thead><tbody>
    @forelse($rows as $r)
        <tr><td>#{{ $r->id }}</td><td>{{ $r->total_amount }}</td><td><span class="status {{ in_array($r->status, ['approved','received','completed'], true) ? 'active' : 'inactive' }}">{{ $r->status }}</span></td></tr>
    @empty<tr><td colspan="3" style="text-align:center;padding:28px;color:#8198ac">خریدی برای نمایش وجود ندارد.</td></tr>@endforelse
    </tbody></table></div>
    @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection