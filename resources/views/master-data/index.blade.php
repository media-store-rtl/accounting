@extends(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers'], true) ? 'layouts.dashboard-shell' : 'layouts.app')

@if(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers'], true))
    @section('title', $definition['title'] ?? 'کالاها و کد کالا')
@endif

@section('content')
@if(in_array(($module ?? null), ['goods', 'locations', 'productions', 'suppliers'], true))
<style>
.master-search{display:flex;gap:8px;margin-bottom:16px}
.master-search input{flex:1;min-width:0;background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px 12px;font:inherit;font-size:11px}
.master-search input::placeholder{color:#71879d}
.master-errors{background:#3b2528;border:1px solid #63343b;color:#ffb8a8;border-radius:12px;padding:12px;margin-bottom:16px;font-size:11px}
.master-success{background:#12372f;border:1px solid #245447;color:#6ee7d0;border-radius:12px;padding:12px;margin-bottom:16px;font-size:11px}
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

<div class="panel">
    <form method="get" class="master-search">
        <input name="q" value="{{ request('q') }}" placeholder="جستجو در فهرست...">
        <button type="submit" class="btn">جستجو</button>
        @if(request()->filled('q'))<a class="btn" href="{{ url()->current() }}">پاک‌کردن</a>@endif
    </form>
    <div class="table-wrap">
        <table class="master-table">
            <thead><tr>@foreach ($definition['fields'] as $field)<th>{{ $field }}</th>@endforeach<th>عملیات</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($definition['fields'] as $field)
                            <td>{{ is_bool($row->$field ?? null) ? (($row->$field ?? false) ? 'بله' : 'خیر') : ($row->$field ?? '—') }}</td>
                        @endforeach
                        <td>
                            <a class="btn" href="{{ route('master.edit', [$module, $row->id]) }}">ویرایش</a>
                            @if ($module === 'productions' && ($row->status ?? null) === 'draft')
                                <form method="post" action="{{ route('master.production.start', $row->id) }}" onsubmit="return confirm('تولید شروع شود؟')">@csrf<button class="btn" type="submit">شروع تولید</button></form>
                            @endif
                            @if (isset($row->is_active) && $row->is_active)
                                <form method="post" action="{{ route('master.deactivate', [$module, $row->id]) }}" onsubmit="return confirm('از غیرفعال کردن مطمئن هستید؟')">@csrf<button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">غیرفعال</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($definition['fields']) + 1 }}" style="text-align:center;padding:30px;color:#71879d">رکوردی برای نمایش وجود ندارد.</td></tr>
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

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <form method="get" class="mb-3">
        <div class="input-group">
            <input name="q" value="{{ request('q') }}" class="form-control" placeholder="جستجو...">
            <button class="btn btn-outline-secondary">جستجو</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>عملیات</th>@foreach ($definition['fields'] as $field)<th>{{ $field }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($definition['fields'] as $field)
                            <td>{{ is_bool($row->$field ?? null) ? (($row->$field ?? false) ? 'بله' : 'خیر') : ($row->$field ?? '—') }}</td>
                        @endforeach
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('master.edit', [$module, $row->id]) }}">ویرایش</a>
                            @if ($module === 'productions' && ($row->status ?? null) === 'draft')
                                <form method="post" action="{{ route('master.production.start', $row->id) }}" class="d-inline" onsubmit="return confirm('تولید شروع شود؟')">@csrf<button class="btn btn-sm btn-success">شروع تولید</button></form>
                            @endif
                            @if (isset($row->is_active) && $row->is_active)
                                <form method="post" action="{{ route('master.deactivate', [$module, $row->id]) }}" class="d-inline" onsubmit="return confirm('از غیرفعال کردن مطمئن هستید؟')">@csrf<button class="btn btn-sm btn-outline-danger">غیرفعال</button></form>
                            @endif
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
@endsection
