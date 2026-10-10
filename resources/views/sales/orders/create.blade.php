@extends('layouts.dashboard-shell')

@section('title', 'ثبت سفارش فروش | حسابداری صنعتی')

@section('content')
<div class="page-heading">
    <div>
        <h1>ثبت سفارش مشتری</h1>
        <p>اطلاعات سفارش و اقلام موردنیاز را وارد کنید.</p>
    </div>
    <a class="btn" href="{{ route('sales.orders.index') }}">بازگشت به سفارش‌ها</a>
</div>

<style>
.order-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
.order-field{min-width:0}.order-field.full{grid-column:1/-1}
.order-field label{display:block;color:#b9cbd9;font-size:11px;margin-bottom:7px}
.order-field input,.order-field select,.order-field textarea,.order-item select,.order-item input{width:100%;min-width:0;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:11px 12px;font-family:inherit;font-size:12px;outline:none}
.order-field textarea{min-height:90px;resize:vertical}
.order-field input:focus,.order-field select:focus,.order-field textarea:focus,.order-item select:focus,.order-item input:focus{border-color:#6ee7d0}
.order-section{grid-column:1/-1;color:#6ee7d0;font-size:12px;font-weight:800;margin-top:8px;padding-bottom:8px;border-bottom:1px solid #1b354b}
.order-items{display:grid;gap:10px}.order-item{display:grid;grid-template-columns:minmax(0,2fr) minmax(110px,1fr);gap:10px}
.order-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:22px}
.order-submit{border:0;border-radius:10px;padding:11px 17px;background:#6ee7d0;color:#06151d;font-family:inherit;font-size:11px;font-weight:800;cursor:pointer}
.order-add{border:1px solid #29465e;border-radius:10px;padding:10px 14px;background:#12314a;color:#e9f3fb;font-family:inherit;font-size:11px;cursor:pointer}
.order-cancel{color:#8ba1b5;text-decoration:none;font-size:11px}
.order-error{color:#ff9d9d;font-size:10px;margin-top:6px}
@media(max-width:650px){.order-grid{grid-template-columns:1fr}.order-section,.order-field.full{grid-column:auto}.order-item{grid-template-columns:1fr}}
</style>

@if($errors->any())
<div class="panel" style="margin-bottom:16px;border-color:#6b343b;color:#ffb8b8">
    <strong style="font-size:11px">لطفاً خطاهای زیر را بررسی کنید:</strong>
    <ul style="font-size:11px;line-height:2;margin-bottom:0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="panel">
    <form method="POST" action="{{ route('sales.orders.store') }}" onsubmit="return submitOrder(event)">
        @csrf
        <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear->id }}">
        <div class="order-grid">
            <div class="order-section">اطلاعات سفارش</div>
            <div class="order-field">
                <label for="customer_id">مشتری *</label>
                <select id="customer_id" name="customer_id" required>
                    @foreach($customers as $c)<option value="{{ $c->id }}" @selected((string) old('customer_id') === (string) $c->id)>{{ $c->code }} - {{ $c->name }}</option>@endforeach
                </select>
                @error('customer_id')<div class="order-error">{{ $message }}</div>@enderror
            </div>
            <div class="order-field">
                <label for="number">شماره سفارش *</label>
                <input id="number" name="number" value="{{ old('number') }}" required>
                @error('number')<div class="order-error">{{ $message }}</div>@enderror
            </div>
            <div class="order-field">
                <label for="ordered_at">تاریخ ثبت *</label>
                <input id="ordered_at" name="ordered_at" type="date" value="{{ old('ordered_at', now()->toDateString()) }}" required>
                @error('ordered_at')<div class="order-error">{{ $message }}</div>@enderror
            </div>
            <div class="order-field">
                <label for="requested_delivery_at">موعد درخواستی تحویل *</label>
                <input id="requested_delivery_at" name="requested_delivery_at" type="date" value="{{ old('requested_delivery_at') }}" required>
                @error('requested_delivery_at')<div class="order-error">{{ $message }}</div>@enderror
            </div>
            <div class="order-section">اقلام سفارش</div>
            <div class="order-field full">
                <div id="items" class="order-items">
                    <div class="order-item">
                        <select name="items[0][goods_id]" aria-label="کالا" required>
                            @foreach($goods as $g)<option value="{{ $g->id }}">{{ $g->code }} - {{ $g->name }}</option>@endforeach
                        </select>
                        <input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('items.0.quantity') }}" placeholder="مقدار" aria-label="مقدار" required>
                    </div>
                </div>
                @error('items')<div class="order-error">{{ $message }}</div>@enderror
                @error('items.0.goods_id')<div class="order-error">{{ $message }}</div>@enderror
                @error('items.0.quantity')<div class="order-error">{{ $message }}</div>@enderror
                <div style="margin-top:12px"><button class="order-add" type="button" onclick="addItem()">+ افزودن قلم</button></div>
            </div>
            <div class="order-section">توضیحات</div>
            <div class="order-field full">
                <label for="notes">توضیحات سفارش</label>
                <textarea id="notes" name="notes" placeholder="توضیحات تکمیلی (اختیاری)">{{ old('notes') }}</textarea>
                @error('notes')<div class="order-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="order-actions">
            <button class="order-submit" type="submit">ثبت سفارش و بررسی موجودی</button>
            <a class="order-cancel" href="{{ route('sales.orders.index') }}">انصراف</a>
        </div>
    </form>
</div>

<script>
let i = 1;
function addItem() {
    const source = document.querySelector('#items .order-item');
    if (!source) return;
    const row = source.cloneNode(true);
    row.querySelectorAll('[name]').forEach((field) => {
        field.name = field.name.replace(/items\[0\]/, 'items[' + i + ']');
        if (field.type === 'number') field.value = '';
    });
    document.getElementById('items').appendChild(row);
    i++;
}
async function submitOrder(e) {
    e.preventDefault();
    const f = e.target;
    const button = f.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const r = await fetch(f.action, {
            method: 'POST',
            headers: {'Accept':'application/json','X-CSRF-TOKEN':f.querySelector('[name=_token]').value},
            body: new FormData(f)
        });
        const j = await r.json();
        if (!r.ok) {
            const messages = j.errors ? Object.values(j.errors).flat().join('، ') : '';
            alert(messages || j.message || 'خطا در ثبت سفارش');
            return false;
        }
        if (j.data && j.data.id) {
            location.href = '/sales/orders/' + j.data.id;
        } else {
            alert('سفارش ارسال شد؛ اما شناسه سفارش در پاسخ سرور موجود نیست.');
        }
    } catch (error) {
        alert('ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
    } finally {
        button.disabled = false;
    }
    return false;
}
</script>
@endsection
