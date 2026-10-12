@extends(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? 'layouts.dashboard-shell' : 'layouts.app')

@if(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true))
    @section('title', $definition['title'] ?? 'کالاها و کد کالا')
@endif

@section('content')
@if(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true))
@php
    $fieldLabels = [
        'code' => 'کد', 'name' => 'نام', 'production_stage_id' => 'مرحله تولید',
        'sequence' => 'ترتیب', 'standard_duration_minutes' => 'مدت استاندارد (دقیقه)',
        'setup_duration_minutes' => 'زمان آماده‌سازی (دقیقه)', 'status' => 'وضعیت',
        'production_route_id' => 'مسیر تولید', 'production_section_id' => 'قسمت تولید',
        'goods_id' => 'کالا', 'fiscal_year_id' => 'سال مالی', 'planned_quantity' => 'مقدار برنامه‌ریزی‌شده',
        'planned_start_at' => 'شروع برنامه‌ریزی‌شده', 'planned_end_at' => 'پایان برنامه‌ریزی‌شده',
        'notes' => 'یادداشت', 'description' => 'توضیحات', 'type' => 'نوع',
        'is_active' => 'وضعیت', 'parent_id' => 'دسته‌بندی بالادست', 'category_id' => 'دسته‌بندی', 'purchasable' => 'قابل خرید', 'producible' => 'قابل تولید', 'sellable' => 'قابل فروش'
    ];
    $valueLabels = [
        'active' => 'فعال', 'inactive' => 'غیرفعال', 'pending' => 'در انتظار',
        'in_progress' => 'در حال اجرا', 'paused' => 'متوقف موقت', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده',
        'draft' => 'پیش‌نویس', 'paused' => 'متوقف موقت', 'pending_review' => 'در انتظار بررسی'
    ];
@endphp
<style>
.master-search{display:flex;gap:8px;margin-bottom:16px}
.master-search input{flex:1;min-width:0;background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px 12px;font:inherit;font-size:11px}
.master-search input::placeholder{color:#71879d}
.master-errors{background:#3b2528;border:1px solid #63343b;color:#ffb8a8;border-radius:12px;padding:12px;margin-bottom:16px;font-size:11px}
.master-success{background:#12372f;border:1px solid #245447;color:#6ee7d0;border-radius:12px;padding:12px;margin-bottom:16px;font-size:11px}
.master-guide{background:#102b3d;border:1px solid #2d5872;color:#d9edf8;border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:11px;line-height:1.9}
.master-guide strong{color:#ffffff}
.master-guide ul{margin:7px 0 0;padding-right:18px}
.master-guide .warning{margin-top:9px;color:#ffd7a1}
.master-table{width:100%;border-collapse:collapse;white-space:nowrap}
.master-table th,.master-table td{padding:12px 10px;border-bottom:1px solid #1b354b;text-align:right;font-size:11px}
.master-table th{background:#0d2235;color:#71879d;font-weight:600}
.master-table tbody tr:hover{background:#0d2235}
.master-table form{display:inline}
.master-table .btn{margin:2px}
@media(max-width:560px){.master-search{flex-direction:column}.master-search .btn{justify-content:center}}
</style>

<div class="page-heading">
    <div>
        <h1>{{ $definition['title'] ?? 'کالاها و کد کالا' }}</h1>
        <p>مدیریت اطلاعات پایه و جستجو در فهرست ثبت‌شده</p>
    </div>
    <a class="btn primary" href="{{ route('master.create', $module) }}">+ ثبت مورد جدید</a>
</div>

@if (session('success'))
    <div class="master-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="master-errors">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

@if ($module === 'production-routes')
    <div class="master-guide" role="note">
        <strong>راهنمای مسیرهای تولید</strong>
        <div>مسیر تولید مشخص می‌کند یک کالا از چه مرحله‌هایی عبور می‌کند تا ساخته شود.</div>
        <ul>
            <li><strong>کد و نام:</strong> مسیر را با شناسه و نام روشن ثبت کنید.</li>
            <li><strong>کالا:</strong> کالایی را انتخاب کنید که این مسیر برای تولید آن است.</li>
            <li><strong>توضیحات:</strong> نکات ویژه مسیر را در صورت نیاز بنویسید.</li>
            <li><strong>وضعیت:</strong> برای استفاده در دستور تولید، مسیر باید فعال باشد.</li>
        </ul>
        <div class="warning"><strong>ترتیب پیشنهادی:</strong> مسیر را ثبت کنید، سپس مرحله‌های آن را تعریف و فعال کنید و بعد عملیات هر مرحله را ثبت کنید.</div>
    </div>
@endif

@if ($module === 'production-stages')
    <div class="master-guide" role="note">
        <strong>راهنمای مراحل تولید</strong>
        <div>هر مرحله بخشی از مسیر ساخت کالا است؛ مانند برش، مونتاژ یا بسته‌بندی.</div>
        <ul>
            <li><strong>کد و نام:</strong> برای شناسایی مرحله، کد و نام روشن وارد کنید.</li>
            <li><strong>مسیر تولید:</strong> مسیر مربوط به کالای موردنظر را انتخاب کنید.</li>
            <li><strong>قسمت تولید:</strong> واحد یا محل مسئول انجام مرحله را مشخص کنید.</li>
            <li><strong>ترتیب:</strong> شماره کوچک‌تر یعنی اجرای زودتر مرحله.</li>
            <li><strong>وضعیت:</strong> فقط مرحله‌های فعال هنگام شروع دستور تولید وارد اجرای آن می‌شوند.</li>
        </ul>
        <div class="warning"><strong>نکته:</strong> پس از ثبت مرحله‌ها، عملیات مربوط به هر مرحله را در صفحه «عملیات تولید» تعریف کنید. تغییر تعریف‌ها لزوماً اجرای دستورهای قبلی را تغییر نمی‌دهد.</div>
    </div>
@endif

@if ($module === 'production-operations')
    <div class="master-guide" role="note">
        <strong>راهنمای عملیات تولید</strong>
        <div>در این بخش، کارهای مشخص هر مرحله از تولید را تعریف می‌کنید؛ برای نمونه برش، مونتاژ یا بسته‌بندی. این اطلاعات، ساختار برنامه تولید را مشخص می‌کنند و به‌تنهایی به معنی ثبت اجرای واقعی کار نیستند.</div>
        <ul>
            <li><strong>کد و نام عملیات:</strong> برای شناسایی روشن هر کار، کد یکتا و نام قابل‌فهم وارد کنید.</li>
            <li><strong>مرحله تولید:</strong> عملیات را به مرحله درست وصل کنید؛ مرحله باید به مسیر تولید موردنظر تعلق داشته باشد.</li>
            <li><strong>ترتیب:</strong> عدد کوچک‌تر یعنی عملیات زودتر در ترتیب مرحله قرار می‌گیرد.</li>
            <li><strong>مدت استاندارد:</strong> زمان معمول انجام کار به دقیقه؛ <strong>زمان آماده‌سازی:</strong> زمان آماده‌کردن دستگاه، ابزار یا ایستگاه پیش از کار.</li>
            <li><strong>وضعیت:</strong> فقط عملیات فعال هنگام شروع دستور تولید برای آن دستور، ردیف اجرای عملیات می‌سازد.</li>
        </ul>
        <div class="warning"><strong>نکته:</strong> ابتدا مسیر تولید و مراحل فعال آن را تعریف کنید، سپس عملیات هر مرحله را ثبت و فعال کنید؛ بعد از آن دستور تولید را شروع کنید تا ردیف‌های اجرای عملیات ایجاد شوند.</div>
    </div>
@endif

@if ($module === 'production-sections')
    <div class="master-guide" role="note">
        <strong>راهنمای قسمت‌های تولید</strong>
        <div>«قسمت تولید» محل یا واحد سازمانی‌ای است که مسئول اجرای بخشی از فرایند ساخت است؛ برای نمونه، کارگاه برش، سالن مونتاژ یا واحد بسته‌بندی. در این صفحه قسمت تولید را به یک محل از فهرست انبارها و محل‌ها وصل می‌کنید.</div>
        <ul>
            <li><strong>محل / انبار:</strong> محل فیزیکی یا سازمانی مربوط به این قسمت را انتخاب کنید. اگر گزینه مناسبی وجود ندارد، ابتدا آن محل را در بخش «انبارها و محل‌ها» تعریف کنید.</li>
            <li><strong>سرپرست:</strong> در صورت مشخص بودن مسئول، پرسنل سرپرست این قسمت را انتخاب کنید؛ اگر هنوز تعیین نشده، می‌توانید خالی بگذارید.</li>
            <li>پس از ثبت قسمت تولید، در بخش «مراحل تولید» آن را به مرحله مناسب وصل کنید و مسیر و ترتیب مرحله را مشخص کنید.</li>
        </ul>
        <div class="warning"><strong>نکته:</strong> ایجاد قسمت تولید به‌تنهایی مسیر یا مرحله تولید نمی‌سازد و تولید را شروع نمی‌کند. برای شروع دستور تولید، باید مسیر و دست‌کم یک مرحله فعال به همان مسیر متصل باشد.</div>
    </div>
@endif

@if ($module === 'productions')
    <div class="master-guide" role="note">
        <strong>راهنمای دستورهای تولید</strong>
        <div>این صفحه برای ثبت و مدیریت <strong>دستور تولید</strong> است. قبل از استفاده از گزینه «شروع تولید» موارد زیر را بررسی کنید:</div>
        <ul>
            <li><strong>شروع تولید به معنی انجام تولید فیزیکی نیست.</strong> با این گزینه فقط دستور از وضعیت پیش‌نویس وارد مرحله اجرا می‌شود.</li>
            <li>با شروع دستور، مراحل و عملیات فعال مسیر تولید برای ثبت اجرای واقعی ایجاد می‌شوند.</li>
            <li><strong>هیچ ماده اولیه‌ای در این مرحله از انبار خارج نمی‌شود</strong> و محصول نهایی نیز به انبار تحویل نمی‌شود.</li>
            <li>پس از شروع، از بخش «ثبت اجرای عملیات تولید» باید مواد مصرف‌شده، محصول خروجی، ضایعات و اطلاعات اجرای واقعی را ثبت کنید.</li>
            <li>عملیات ثبت‌شده برای بررسی و تأیید سرپرست تولید ارسال می‌شود؛ ثبت عملیات به معنی تأیید نهایی نیست.</li>
        </ul>
        <div class="warning"><strong>نکته:</strong> قبل از زدن «شروع تولید»، کالا، مسیر تولید، مقدار برنامه‌ریزی‌شده و مراحل/عملیات فعال را بررسی کنید. این اقدام فقط برای دستورهای «پیش‌نویس» در دسترس است.</div>
    </div>
@endif

<div class="panel">
    <form method="get" class="master-search">
        <input name="q" value="{{ request('q') }}" placeholder="جستجو در فهرست...">
        <button type="submit" class="btn">جستجو</button>
        @if(request()->filled('q'))<a class="btn" href="{{ url()->current() }}">پاک‌کردن</a>@endif
    </form>
    <div class="table-wrap">
        <table class="master-table">
            @if ($module === 'productions')
                <thead><tr><th>شماره دستور</th><th>کالا</th><th>مسیر تولید</th><th>سال مالی</th><th>مقدار برنامه‌ریزی‌شده</th><th>شروع برنامه‌ریزی‌شده</th><th>پایان برنامه‌ریزی‌شده</th><th>یادداشت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            @else
                <thead><tr>@if ($module === 'production-sections')<th>محل / انبار</th><th>سرپرست</th>@else @foreach ($definition['fields'] as $field)<th>{{ $fieldLabels[$field] ?? $field }}</th>@endforeach @endif<th>عملیات</th></tr></thead>
            @endif
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @if ($module === 'productions')
                            <td>{{ $row->number ?? '—' }}</td>
                            <td>{{ $row->goods_name ?? 'کالای نامشخص' }}</td>
                            <td>{{ $row->production_route_name ?? 'مسیر نامشخص' }}</td>
                            <td>{{ $row->fiscal_year_name ?? 'سال مالی نامشخص' }}</td>
                            <td>{{ rtrim(rtrim(number_format((float)($row->planned_quantity ?? 0), 4, '.', ''), '0'), '.') }}</td>
                            <td>@if($row->planned_start_at)<time class="production-jalali-date" datetime="{{ $row->planned_start_at }}">{{ $row->planned_start_at }}</time>@else—@endif</td>
                            <td>@if($row->planned_end_at)<time class="production-jalali-date" datetime="{{ $row->planned_end_at }}">{{ $row->planned_end_at }}</time>@else—@endif</td>
                            <td>{{ $row->notes ?: '—' }}</td>
                            <td>{{ match((string)($row->status ?? '')) { 'draft' => 'پیش‌نویس', 'in_progress' => 'در حال اجرا', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', default => ($row->status ?? 'نامشخص') } }}</td>
                        @else
                            @if ($module === 'production-sections')
                                <td>{{ $row->location_name ?? $row->location_id ?? '—' }}</td>
                                <td>{{ $row->supervisor_name ?? $row->supervisor_personnel_id ?? '—' }}</td>
                            @else
                                @foreach ($definition['fields'] as $field)
                                    <td>{{ is_bool($row->$field ?? null) ? (($row->$field ?? false) ? 'بله' : 'خیر') : ($valueLabels[(string)($row->$field ?? '')] ?? (($row->$field ?? null) === null || ($row->$field ?? '') === '' ? '—' : $row->$field)) }}</td>
                                @endforeach
                            @endif
                        @endif
                        <td>
                            @if ($module !== 'productions' || ($row->status ?? null) === 'draft')
                                <a class="btn" href="{{ route('master.edit', [$module, $row->id]) }}">ویرایش</a>
                            @else
                                <span style="font-size:10px;color:#8198ac">ویرایش پس از شروع غیرفعال است</span>
                            @endif
                            @if ($module === 'productions' && ($row->status ?? null) === 'draft')
                                <form method="post" action="{{ route('master.production.start', $row->id) }}" onsubmit="return confirm('توجه: با شروع، دستور وارد اجرا می‌شود و دیگر به پیش‌نویس برنمی‌گردد. برای توقف موقت از «توقف» و سپس «ازسرگیری» استفاده کنید. آیا مطمئن هستید؟')">@csrf<button class="btn" type="submit">شروع تولید</button></form>
                            @elseif ($module === 'productions' && ($row->status ?? null) === 'in_progress')
                                <form method="post" action="{{ route('master.production.pause', $row->id) }}" onsubmit="return confirm('تولید موقتاً متوقف شود؟ تا زمان ازسرگیری، ثبت اجرای عملیات جدید مسدود می‌شود.')">@csrf<button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">توقف موقت</button></form>
                            @elseif ($module === 'productions' && ($row->status ?? null) === 'paused')
                                <form method="post" action="{{ route('master.production.resume', $row->id) }}" onsubmit="return confirm('اجرای این دستور تولید از سر گرفته شود؟')">@csrf<button class="btn" type="submit">ازسرگیری تولید</button></form>
                            @endif
                            @if (isset($row->is_active) && $row->is_active)
                                <form method="post" action="{{ route('master.deactivate', [$module, $row->id]) }}" onsubmit="return confirm('از غیرفعال کردن مطمئن هستید؟')">@csrf<button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">غیرفعال</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($module === 'productions' ? 10 : count($definition['fields']) + 1) }}" style="text-align:center;padding:30px;color:#71879d">رکوردی برای نمایش وجود ندارد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
@else
<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $definition['title'] }}</h1>
        <a class="btn btn-primary" href="{{ route('master.create', $module) }}">ثبت جدید</a>
    </div>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="get" class="mb-3"><div class="input-group"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="جستجو..."><button class="btn btn-outline-secondary">جستجو</button></div></form>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>عملیات</th>@foreach ($definition['fields'] as $field)<th>{{ $field }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($definition['fields'] as $field)<td>{{ is_bool($row->$field ?? null) ? (($row->$field ?? false) ? 'بله' : 'خیر') : ($row->$field ?? '—') }}</td>@endforeach
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('master.edit', [$module, $row->id]) }}">ویرایش</a>
                            @if ($module === 'productions' && ($row->status ?? null) === 'draft')<form method="post" action="{{ route('master.production.start', $row->id) }}" class="d-inline" onsubmit="return confirm('توجه: با ادامه، این دستور از وضعیت پیش‌نویس وارد مرحله اجرا می‌شود و مراحل و عملیات آن ایجاد می‌شوند. آیا مطمئن هستید؟')">@csrf<button class="btn btn-sm btn-success">شروع تولید</button></form>@endif
                            @if (isset($row->is_active) && $row->is_active)<form method="post" action="{{ route('master.deactivate', [$module, $row->id]) }}" class="d-inline" onsubmit="return confirm('از غیرفعال کردن مطمئن هستید؟')">@csrf<button class="btn btn-sm btn-outline-danger">غیرفعال</button></form>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($definition['fields']) + 1 }}" class="text-center py-5">رکوردی وجود ندارد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</div>
@endif

@if ($module === 'productions')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.production-jalali-date[datetime]').forEach(function (element) {
        const value = element.getAttribute('datetime');
        const match = value && value.match(/^(\\d{4})-(\\d{2})-(\\d{2})(?:[ T](\\d{2}):(\\d{2}))?/);
        if (!match) return;
        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4] || 12), Number(match[5] || 0));
        element.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: match[4] ? '2-digit' : undefined, minute: match[4] ? '2-digit' : undefined,
            hourCycle: 'h23'
        }).format(date);
        element.title = value;
    });
});
</script>
@endif

@endsection
