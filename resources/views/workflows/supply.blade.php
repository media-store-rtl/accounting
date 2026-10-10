@extends('layouts.dashboard-shell')

@section('title', 'درخواست‌های تأمین')

@section('content')
<div class="page-heading">
    <div>
        <h1>درخواست‌های تأمین</h1>
        <p>ثبت درخواست مواد و مشاهده وضعیت درخواست‌های تأمین مجموعه</p>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">
        <strong>لطفاً موارد زیر را بررسی کنید:</strong>
        <ul style="margin:8px 0 0;padding-right:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="margin-bottom:18px">
    <div style="margin-bottom:16px">
        <h2 style="font-size:15px;margin:0 0 6px">ثبت درخواست جدید</h2>
        <p style="font-size:11px;color:#8198ac;margin:0">اطلاعات موردنیاز برای تأمین کالا را وارد کنید.</p>
    </div>

    <form method="POST" action="{{ route('workflows.supply.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                سال مالی
                <select name="fiscal_year_id" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected((string) old('fiscal_year_id') === (string) $y->id)>{{ $y->year ?? $y->id }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                تولید (اختیاری)
                <select name="production_id" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">—</option>
                    @foreach($productions as $p)
                        <option value="{{ $p->id }}" @selected((string) old('production_id') === (string) $p->id)>{{ $p->number }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                سفارش (اختیاری)
                <select name="order_id" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">—</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->id }}" @selected((string) old('order_id') === (string) $o->id)>{{ $o->number }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                کالا
                <select name="items[0][goods_id]" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($goods as $g)
                        <option value="{{ $g->id }}" @selected((string) old('items.0.goods_id') === (string) $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                مقدار
                <input name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                نیاز تا
                <input name="needed_at" type="date" value="{{ old('needed_at') }}" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark">
            </label>
        </div>

        <div style="margin-top:18px">
            <button type="submit" class="btn primary">ثبت درخواست تأمین</button>
        </div>
    </form>
</div>

<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <div>
            <h2 style="font-size:15px;margin:0 0 5px">درخواست‌های ثبت‌شده</h2>
            <p style="font-size:11px;color:#8198ac;margin:0">فهرست درخواست‌های تأمین مجموعه</p>
        </div>
        <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} درخواست</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>وضعیت</th>
                    <th>تاریخ نیاز</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>#{{ $r->id }}</td>
                        <td><span class="status {{ in_array($r->status, ['approved', 'stock_available', 'partially_supplied', 'supplied', 'completed'], true) ? 'active' : 'inactive' }}">{{ $r->status }}</span></td>
                        <td>{{ $r->needed_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;padding:28px;color:#8198ac">درخواستی برای نمایش وجود ندارد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($rows, 'links'))
        <div class="pagination">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
