@extends('layouts.dashboard-shell')

@section('title', 'درخواست‌های مواد اولیه')

@section('content')

<div class="page-heading">
    <div>
        <h1>درخواست‌های مواد اولیه</h1>
        <p>مشاهده وضعیت درخواست‌های مواد اولیه و پیگیری بررسی انبار</p>
    </div>
    <a href="{{ route('workflows.supply.create') }}" class="btn primary">ثبت درخواست جدید</a>
</div>

@if(session('success'))
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">
        {{ session('success') }}
    </div>
@endif

<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <div>
            <h2 style="font-size:15px;margin:0 0 5px">درخواست‌های ثبت‌شده</h2>
            <p style="font-size:11px;color:#8198ac;margin:0">فهرست درخواست‌های مواد اولیه مجموعه</p>
        </div>
        <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} درخواست</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>اقلام درخواست</th>
                    @if($canReviewStock)<th>موجودی انبار</th>@endif
                    <th>وضعیت</th>
                    <th>تاریخ نیاز</th>
                    @if($canReviewStock)<th>اقدام انبار</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>#{{ $r->id }}</td>
                        <td>
                            @foreach($r->items as $item)
                                <div style="margin-bottom:5px">{{ $item->goods_name }} — درخواست: {{ rtrim(rtrim(number_format((float)$item->requested_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</div>
                                @if($canReviewStock && $r->status !== 'checking_stock')
                                    <small style="color:#9eb2c4">قابل تأمین: {{ rtrim(rtrim(number_format((float)$item->available_quantity, 4, '.', ','), '0'), '.') ?: '0' }} | کسری: {{ rtrim(rtrim(number_format((float)$item->shortage_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</small>
                                @endif
                            @endforeach
                        </td>
                        @if($canReviewStock)
                            <td>
                                @foreach($r->items as $item)
                                    <div style="margin-bottom:5px">{{ $item->goods_name }}: <strong>{{ rtrim(rtrim(number_format((float)($item->warehouse_quantity ?? 0), 4, '.', ','), '0'), '.') ?: '0' }}</strong></div>
                                @endforeach
                            </td>
                        @endif
                        <td><span class="status {{ in_array($r->status, ['stock_available', 'partially_supplied', 'supplied', 'fulfilled', 'completed'], true) ? 'active' : 'inactive' }}">{{ ['checking_stock'=>'در انتظار بررسی انبار','shortage_pending'=>'کسری تأییدشده؛ در انتظار تدارکات','stock_available'=>'موجودی کافی؛ آماده تحویل','rejected'=>'ردشده توسط انبار','purchasing'=>'در حال خرید','purchased'=>'خریداری شده','partially_supplied'=>'بخشی تحویل شده','fulfilled'=>'تحویل کامل','cancelled'=>'لغوشده'][$r->status] ?? $r->status }}</span></td>
                        <td>
                            @if($r->needed_at)
                                <time class="jalali-date" datetime="{{ $r->needed_at }}">{{ $r->needed_at }}</time>
                            @else
                                —
                            @endif
                        </td>
                        @if($canReviewStock)
                            <td>
                                @if($r->status === 'checking_stock')
                                    <form method="POST" action="{{ route('workflows.supply.stock-review', $r->id) }}" style="min-width:190px">
                                        @csrf
                                        <label style="display:block;margin-bottom:7px;font-size:10px;color:#9eb2c4">یادداشت انباردار (اختیاری)</label>
                                        <input name="warehouse_note" maxlength="1000" placeholder="توضیح بررسی موجودی" style="width:100%;box-sizing:border-box;margin-bottom:8px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit">
                                        <button type="submit" class="btn primary" onclick="return confirm('موجودی انبار بررسی شده و نتیجه برای درخواست ثبت شود؟')">تأیید نتیجه بررسی</button>
                                    </form>
                                @else
                                    <span style="color:#8198ac;font-size:10px">بررسی انجام شده</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canReviewStock ? 6 : 4 }}" style="text-align:center;padding:28px;color:#8198ac">درخواستی برای نمایش وجود ندارد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($rows, 'links'))
        <div class="pagination">{{ $rows->links() }}</div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.jalali-date[datetime]').forEach(function (element) {
        const value = element.getAttribute('datetime');
        const match = value && value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return;
        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12);
        element.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit'
        }).format(date);
    });
});
</script>
@endsection
