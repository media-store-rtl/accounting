@extends('layouts.dashboard-shell')

@section('title', 'کارکرد تولید')

@section('content')
<div class="page-heading">
    <div>
        <h1>کارکرد تولید</h1>
        <p>ثبت زمان یا مقدار کار پرسنل روی عملیات تولید و مشاهده سوابق کارکرد</p>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">
        <strong>ثبت کارکرد انجام نشد؛ موارد زیر را بررسی کنید:</strong>
        <ul style="margin:8px 0 0;padding-right:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="margin-bottom:18px">
    <div style="margin-bottom:14px">
        <h2 style="font-size:15px;margin:0 0 6px">راهنمای ثبت کارکرد</h2>
        <p style="font-size:11px;color:#8198ac;line-height:2;margin:0">کارکرد ثبت‌شده ابتدا برای بررسی می‌ماند و تا زمان تأیید، هزینه نهایی کارکرد محسوب نمی‌شود.</p>
    </div>
    <div style="padding:13px 15px;border:1px solid #29465e;border-radius:12px;background:#0d2235;color:#c5d6e4;font-size:11px;line-height:2">
        <ol style="margin:0;padding-right:20px">
            <li>اجرای عملیات مربوط به تولید موردنظر را انتخاب کنید؛ شماره تولید، مرحله و نام عملیات در فهرست نمایش داده می‌شود.</li>
            <li>پرسنلی را انتخاب کنید که کار را انجام داده است.</li>
            <li>نوع اندازه‌گیری را مشخص کنید: «ساعت» برای زمان کار یا «واحد» برای تعداد کار انجام‌شده.</li>
            <li>مقدار را بر اساس نوع انتخاب‌شده وارد و تاریخ و ساعت انجام کار را ثبت کنید.</li>
            <li>برای همان پرسنل، نوع اندازه‌گیری و تاریخ باید نرخ دستمزد معتبر تعریف شده باشد؛ در غیر این صورت ثبت انجام نمی‌شود.</li>
            <li>روی «ثبت کارکرد» بزنید. ردیف جدید با وضعیت «در انتظار تأیید» در فهرست پایین ذخیره می‌شود.</li>
        </ol>
        <p style="margin:8px 0 0;color:#ffcf8b">توجه: ثبت کارکرد به‌تنهایی به معنی تأیید هزینه نیست. هزینه پس از تأیید مسئول مجاز نهایی می‌شود.</p>
    </div>
</div>

<div class="panel" style="margin-bottom:18px">
    <div style="margin-bottom:16px">
        <h2 style="font-size:15px;margin:0 0 6px">ثبت کارکرد جدید</h2>
        <p style="font-size:11px;color:#8198ac;margin:0">فیلدهای ستاره‌دار الزامی هستند.</p>
    </div>

    @if($runs->isEmpty())
        <div style="padding:12px 14px;margin-bottom:16px;border:1px solid #725a31;border-radius:10px;background:#2c261a;color:#ffcf8b;font-size:11px;line-height:2">
            اجرای عملیاتی برای این مجموعه پیدا نشد. ابتدا یک دستور تولید را شروع کنید تا اجراهای عملیات در این فهرست ظاهر شوند.
        </div>
    @endif

    @if($personnel->isEmpty())
        <div style="padding:12px 14px;margin-bottom:16px;border:1px solid #725a31;border-radius:10px;background:#2c261a;color:#ffcf8b;font-size:11px;line-height:2">
            پرسنل فعال برای انتخاب وجود ندارد. ابتدا پرسنل را در اطلاعات پایه ثبت و فعال کنید.
        </div>
    @endif

    <form method="POST" action="{{ route('workflows.labor.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,230px),1fr));gap:16px 14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                اجرای عملیات *
                <small style="color:#71879d;font-size:10px;font-weight:400">شماره تولید، مرحله و نام عملیات را بررسی کنید.</small>
                <select name="production_operation_run_id" required class="form-select" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">انتخاب اجرای عملیات</option>
                    @foreach($runs as $x)
                        <option value="{{ $x->id }}" @selected((string)old('production_operation_run_id') === (string)$x->id)>
                            {{ $x->number }} — {{ $x->stage_name ?: 'مرحله بدون نام' }} / {{ $x->operation_name ?: 'عملیات '.$x->id }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                پرسنل *
                <small style="color:#71879d;font-size:10px;font-weight:400">فقط پرسنل فعال قابل انتخاب است.</small>
                <select name="personnel_id" required class="form-select" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">انتخاب پرسنل</option>
                    @foreach($personnel as $p)
                        <option value="{{ $p->id }}" @selected((string)old('personnel_id') === (string)$p->id)>{{ $p->code ? $p->code.' — ' : '' }}{{ $p->name }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                نوع اندازه‌گیری *
                <small style="color:#71879d;font-size:10px;font-weight:400">نوع باید با نرخ دستمزد تعریف‌شده مطابقت داشته باشد.</small>
                <select name="measure_type" required class="form-select" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="hours" @selected(old('measure_type', 'hours') === 'hours')>ساعت</option>
                    <option value="quantity" @selected(old('measure_type') === 'quantity')>واحد / تعداد</option>
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                مقدار *
                <small style="color:#71879d;font-size:10px;font-weight:400">مقدار باید بیشتر از صفر باشد.</small>
                <input name="measure_quantity" type="number" min="0.0001" step="0.0001" value="{{ old('measure_quantity') }}" required class="form-input" style="width:100%">
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                تاریخ و زمان کار *
                <small style="color:#71879d;font-size:10px;font-weight:400">تاریخی را وارد کنید که کار واقعاً انجام شده است.</small>
                <input name="worked_at" type="datetime-local" value="{{ old('worked_at', now()->format('Y-m-d\TH:i')) }}" required class="form-input" style="width:100%;color-scheme:dark">
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                یادداشت (اختیاری)
                <small style="color:#71879d;font-size:10px;font-weight:400">توضیح کوتاهی درباره کار انجام‌شده بنویسید.</small>
                <textarea name="notes" rows="2" maxlength="2000" class="form-input" style="width:100%;resize:vertical">{{ old('notes') }}</textarea>
            </label>
        </div>
        <div style="margin-top:18px">
            <button type="submit" class="btn primary" @disabled($runs->isEmpty() || $personnel->isEmpty())>ثبت کارکرد</button>
        </div>
    </form>
</div>

<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <div>
            <h2 style="font-size:15px;margin:0 0 5px">سوابق کارکرد ثبت‌شده</h2>
            <p style="font-size:11px;color:#8198ac;margin:0">کارکردهای این مجموعه به همراه وضعیت بررسی و هزینه محاسبه‌شده</p>
        </div>
        <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} کارکرد</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>تولید / عملیات</th>
                    <th>پرسنل</th>
                    <th>نوع کارکرد</th>
                    <th>مقدار</th>
                    <th>زمان انجام</th>
                    <th>وضعیت</th>
                    <th>هزینه</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    @php
                        $statusLabels = ['pending' => 'در انتظار تأیید', 'approved' => 'تأییدشده', 'rejected' => 'ردشده'];
                        $measureLabels = ['hours' => 'ساعت', 'quantity' => 'واحد / تعداد'];
                    @endphp
                    <tr>
                        <td>#{{ $r->id }}</td>
                        <td>
                            <strong>{{ $r->production_number ?: '—' }}</strong>
                            <div style="font-size:11px;color:#8198ac;margin-top:4px">{{ $r->stage_name ?: '—' }} / {{ $r->operation_name ?: 'عملیات '.$r->production_operation_run_id }}</div>
                        </td>
                        <td>{{ $r->personnel_name ?: '—' }}</td>
                        <td>{{ $measureLabels[$r->measure_type] ?? $r->measure_type }}</td>
                        <td>{{ rtrim(rtrim(number_format((float)$r->measure_quantity, 4, '.', ','), '0'), '.') }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($r->worked_at)->format('Y/m/d H:i') }}</td>
                        <td>
                            <span class="status {{ $r->status === 'approved' ? 'active' : 'inactive' }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                            @if($r->rejection_reason)
                                <div style="font-size:10px;color:#ffb8a8;margin-top:5px">{{ $r->rejection_reason }}</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap">{{ number_format((float)$r->total_cost, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;padding:28px;color:#8198ac">هنوز کارکردی ثبت نشده است. فرم بالا برای ثبت اولین کارکرد در دسترس است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows, 'links'))
        <div class="pagination">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
