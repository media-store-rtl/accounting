@extends('layouts.app')
@section('content')
<div class="container" dir="rtl">
    <h1>کارکرد تولید</h1>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <form method="post" action="{{ route('workflows.labor.store') }}" class="card p-4 mb-4">
        @csrf
        <h5>ثبت کارکرد یک قسمت از شیفت</h5>
        <p class="text-muted">برای کارگرِ دارای چند قسمت کاری در یک شیفت، برای هر قسمت یک ردیف جدا ثبت کنید. ساعت و تعداد در هر ردیف ذخیره می‌شوند.</p>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">سال مالی</label><select name="fiscal_year_id" class="form-select" required><option value="">انتخاب سال مالی</option>@foreach($fiscalYears as $year)<option value="{{ $year->id }}" @selected((string)old('fiscal_year_id')===(string)$year->id)>{{ $year->name ?? $year->title ?? $year->id }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">کارگر</label><select name="personnel_id" class="form-select" required><option value="">انتخاب کارگر</option>@foreach($personnel as $p)<option value="{{ $p->id }}" @selected((string)old('personnel_id')===(string)$p->id)>{{ $p->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">قسمت کاری</label><select name="production_section_id" class="form-select" required><option value="">انتخاب قسمت</option>@foreach($sections as $s)<option value="{{ $s->id }}" @selected((string)old('production_section_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">محصول مرتبط با کار</label><select name="goods_id" class="form-select" required><option value="">انتخاب محصول</option>@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('goods_id')===(string)$g->id)>{{ $g->code ? $g->code.' — ' : '' }}{{ $g->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">تاریخ و زمان کار</label><input name="worked_at" type="datetime-local" value="{{ old('worked_at', now()->format('Y-m-d\\TH:i')) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">نام شیفت (اختیاری)</label><input name="shift_name" value="{{ old('shift_name') }}" class="form-control" placeholder="مثلاً صبح"></div>
            <div class="col-md-3"><label class="form-label">ساعت کار</label><input name="hours_worked" type="number" min="0.0001" step="0.0001" value="{{ old('hours_worked') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">تعداد کارشده در این قسمت</label><input name="quantity_completed" type="number" min="0" step="0.0001" value="{{ old('quantity_completed', 0) }}" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">مبنای محاسبه دستمزد</label><select name="pay_basis" class="form-select" required><option value="hourly" @selected(old('pay_basis','hourly')==='hourly')>ساعتی</option><option value="per_unit" @selected(old('pay_basis')==='per_unit')>به‌ازای هر واحد</option></select><small class="text-muted">ساعت و تعداد هر دو ذخیره می‌شوند؛ فقط مبنای تعیین‌شده برای محاسبه دستمزد استفاده می‌شود.</small></div>
            <div class="col-12"><label class="form-label">یادداشت</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
        </div>
        <button class="btn btn-primary mt-3">ثبت برای تأیید سرپرست</button>
    </form>

    <div class="table-responsive"><table class="table table-striped align-middle">
        <thead><tr><th>تاریخ</th><th>کارگر</th><th>قسمت</th><th>محصول</th><th>ساعت</th><th>تعداد</th><th>مبنای دستمزد</th><th>وضعیت</th><th>هزینه</th>@if($canReview)<th>بررسی</th>@endif</tr></thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r->shift_date ?? substr((string)$r->worked_at,0,10) }}</td><td>{{ $r->personnel_name ?? $r->personnel_id }}</td><td>{{ $r->section_name ?? '—' }}</td><td>{{ $r->goods_name ?? '—' }}</td>
                <td>{{ $r->hours_worked ?? ($r->measure_type==='hours' ? $r->measure_quantity : '—') }}</td><td>{{ $r->quantity_completed ?? ($r->measure_type==='quantity' ? $r->measure_quantity : '—') }}</td>
                <td>{{ $r->pay_basis==='hourly' ? 'ساعتی' : ($r->pay_basis==='per_unit' ? 'تعدادی' : $r->measure_type) }}</td><td>{{ $r->status }}</td><td>{{ number_format((float)$r->total_cost, 2) }}</td>
                @if($canReview)<td>@if($r->status==='pending')
                    <form method="post" action="{{ route('production.labor.review', $r->id) }}" class="d-inline">@csrf<input type="hidden" name="approve" value="1"><button class="btn btn-sm btn-success">تأیید</button></form>
                    <form method="post" action="{{ route('production.labor.review', $r->id) }}" class="d-inline">@csrf<input type="hidden" name="approve" value="0"><input name="reason" required class="form-control form-control-sm d-inline-block" style="width:130px" placeholder="دلیل رد"><button class="btn btn-sm btn-danger">رد</button></form>
                @else—@endif</td>@endif
            </tr>
        @empty<tr><td colspan="{{ $canReview ? 10 : 9 }}" class="text-center">کارکردی ثبت نشده است.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $rows->links() }}
</div>
@endsection
