@extends('layouts.dashboard-shell')

@section('title', 'رسیدهای خرید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.receipt-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>رسیدهای خرید</h1><p>ثبت دریافت کالا و مدیریت تأیید رسیدهای انبار</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if(session('error'))<div role="alert" style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">{{ session('error') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:18px">
    <h2 style="font-size:15px;margin:0 0 16px">ثبت رسید جدید</h2>
    <form method="POST" action="{{ route('workflows.receipts.store') }}">@csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">خرید
                <select id="receipt-purchase-select" name="purchase_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">انتخاب خرید</option>@foreach($purchases as $p)<option value="{{ $p->id }}" @selected((string)old('purchase_id')===(string)$p->id)>خرید #{{ $p->id }}</option>@endforeach</select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">انبار
                <select name="warehouse_location_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)old('warehouse_location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach</select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ دریافت
                <input id="received_date_picker" type="text" class="receipt-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required>
                <input id="received_date_iso" type="hidden" value="{{ old('received_at') ? substr(old('received_at'), 0, 10) : '' }}">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">ساعت دریافت
                <input id="received_time" type="time" value="{{ old('received_at') ? substr(old('received_at'), 11, 5) : '' }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark">
            </label>
            <input name="received_at" type="hidden" value="{{ old('received_at') }}">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا
                <select id="receipt-goods-select" name="items[0][goods_id]" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">ابتدا خرید را انتخاب کنید</option></select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار
                <input id="receipt-quantity-input" name="items[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('items.0.quantity') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                <span id="receipt-quantity-hint" aria-live="polite" style="color:#9eb2c4;line-height:1.8;font-size:11px">پس از انتخاب خرید و کالا، مقدار باقی‌مانده به‌صورت خودکار پیشنهاد می‌شود.</span>
                <span id="receipt-quantity-warning" role="alert" style="display:none;color:#ffb8a8;line-height:1.8;font-size:11px"></span>
                @error('items.0.quantity')<span style="color:#ffb8a8;line-height:1.8;font-size:11px">{{ $message }}</span>@enderror
            </label>
            @error('warehouse_location_id')<div style="color:#ffb8a8;font-size:11px;align-self:end">{{ $message }}</div>@enderror
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">توضیحات (اختیاری)
                <input name="notes" value="{{ old('notes') }}" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
        </div>
        <div style="margin-top:18px"><button class="btn primary" type="submit">ثبت رسید</button></div>
    </form>
</div>

<div class="panel"><h2 style="font-size:15px;margin:0 0 14px">رسیدهای ثبت‌شده</h2>
<div class="table-wrap"><table><thead><tr><th>شناسه</th><th>خرید</th><th>اقلام و مقدار</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>#{{ $row->id }}</td><td>#{{ $row->purchase_id }}</td><td>@forelse($row->items as $item)<div>{{ $item->goods_name }} — {{ number_format((float)$item->quantity,4,'.',',') }}</div>@empty—@endforelse</td><td><span class="status {{ $row->status === 'approved' ? 'active' : 'inactive' }}">{{ $row->status === 'pending' ? 'در انتظار تأیید' : ($row->status === 'approved' ? 'تأیید شده' : $row->status) }}</span></td><td>@if($row->status==='pending')<form method="POST" action="{{ route('workflows.receipts.approve',$row->id) }}">@csrf<button class="btn" type="submit">تأیید رسید</button></form>@else—@endif</td></tr>@empty<tr><td colspan="5" style="text-align:center;padding:28px;color:#8198ac">رسیدی برای نمایش وجود ندارد.</td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#received_date_picker', '#received_date_iso');
});
</script>
<script>
const receiptForm = document.querySelector('form[action="{{ route('workflows.receipts.store') }}"]');
if (receiptForm) {
    const purchaseSelect = document.getElementById('receipt-purchase-select');
    const goodsSelect = document.getElementById('receipt-goods-select');
    const quantityInput = document.getElementById('receipt-quantity-input');
    const quantityHint = document.getElementById('receipt-quantity-hint');
    const quantityWarning = document.getElementById('receipt-quantity-warning');
    const purchaseItems = @json($purchaseItems);
    const oldGoodsId = @json(old('items.0.goods_id'));
    const oldQuantity = @json(old('items.0.quantity'));
    const formatQuantity = value => Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 4 });

    function refreshQuantityHint() {
        const purchaseId = purchaseSelect.value;
        const item = (purchaseItems[purchaseId] || []).find(row => String(row.goods_id) === String(goodsSelect.value));
        if (!item) {
            quantityHint.textContent = 'برای خرید انتخاب‌شده کالای قابل رسید وجود ندارد.';
            quantityWarning.style.display = 'none';
            return;
        }
        quantityHint.textContent = 'مقدار خرید: ' + formatQuantity(item.purchase_quantity)
            + '؛ قبلاً ثبت‌شده یا در انتظار تأیید: ' + formatQuantity(item.received_quantity)
            + '؛ باقی‌مانده قابل رسید: ' + formatQuantity(item.remaining_quantity)
            + '. مقدار پیشنهادی برابر باقی‌مانده است و می‌توانید برای تحویل مرحله‌ای آن را کمتر کنید.';
        const entered = Number(quantityInput.value || 0);
        if (entered > Number(item.remaining_quantity) + 0.0000001) {
            quantityWarning.textContent = 'مقدار واردشده از باقی‌مانده خرید بیشتر است؛ حداکثر مقدار قابل ثبت ' + formatQuantity(item.remaining_quantity) + ' است.';
            quantityWarning.style.display = 'block';
        } else {
            quantityWarning.textContent = '';
            quantityWarning.style.display = 'none';
        }
    }

    function refreshGoods(preserveOldValue = false) {
        const purchaseId = purchaseSelect.value;
        const items = purchaseItems[purchaseId] || [];
        goodsSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = items.length ? 'انتخاب کالا از همین خرید' : 'برای این خرید قلم قابل رسید وجود ندارد';
        goodsSelect.appendChild(placeholder);
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = String(item.goods_id);
            option.disabled = Number(item.remaining_quantity) <= 0;
            option.textContent = item.goods_name + ' — باقی‌مانده ' + formatQuantity(item.remaining_quantity) + ' از ' + formatQuantity(item.purchase_quantity);
            goodsSelect.appendChild(option);
        });
        const preferred = preserveOldValue && oldGoodsId !== null ? String(oldGoodsId) : '';
        const available = items.find(item => Number(item.remaining_quantity) > 0 && String(item.goods_id) === preferred)
            || items.find(item => Number(item.remaining_quantity) > 0);
        goodsSelect.value = available ? String(available.goods_id) : '';
        if (preserveOldValue && oldQuantity !== null && oldQuantity !== '' && available && String(available.goods_id) === preferred) {
            quantityInput.value = oldQuantity;
        } else {
            quantityInput.value = available ? String(available.remaining_quantity) : '';
        }
        refreshQuantityHint();
    }

    purchaseSelect.addEventListener('change', () => refreshGoods(false));
    goodsSelect.addEventListener('change', () => {
        const item = (purchaseItems[purchaseSelect.value] || []).find(row => String(row.goods_id) === String(goodsSelect.value));
        quantityInput.value = item ? String(item.remaining_quantity) : '';
        refreshQuantityHint();
    });
    quantityInput.addEventListener('input', refreshQuantityHint);
    refreshGoods(true);

    receiptForm.addEventListener('submit', function (event) {
        const date = document.getElementById('received_date_iso').value;
        const time = document.getElementById('received_time').value;
        if (!date || !time) {
            event.preventDefault();
            alert('لطفاً تاریخ شمسی و ساعت دریافت را انتخاب کنید.');
            return;
        }
        if (!purchaseSelect.value || !goodsSelect.value) {
            event.preventDefault();
            alert('لطفاً خرید و یکی از کالاهای همان خرید را انتخاب کنید.');
            return;
        }
        receiptForm.querySelector('[name="received_at"]').value = date + 'T' + time;
    });
}
</script>
@endsection