@extends('layouts.dashboard-shell')

@section('title', 'دستمزد پرسنل')

@section('content')
<div class="page-heading">
    <div>
        <h1>دستمزد پرسنل</h1>
        <p>{{ $personnel->name }} — کد پرسنلی: {{ $personnel->code }}</p>
    </div>
    <a class="btn" href="{{ route('personnel.index') }}">بازگشت به فهرست پرسنل</a>
</div>

@if(session('success'))
    <div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="panel" style="margin-bottom:16px;border-color:#63343b;color:#ffb8a8">
        <ul style="margin:0;padding-right:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="margin-bottom:20px">
    <h2 style="margin-top:0">ثبت نرخ جدید</h2>
    <p>نرخ جدید فقط برای بازهٔ اعتبار تعیین‌شده اعمال می‌شود؛ نرخ‌های قبلی برای حفظ محاسبات تاریخی نگه داشته می‌شوند.</p>
    <form method="POST" action="{{ route('personnel.labor-rates.store', $personnel) }}">
        @csrf
        <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px">
            <label>نوع دستمزد
                <select name="rate_type" required>
                    <option value="hourly" @selected(old('rate_type') === 'hourly')>ساعتی</option>
                    <option value="per_unit" @selected(old('rate_type') === 'per_unit')>به‌ازای هر واحد تولید</option>
                </select>
            </label>
            <label>مبلغ نرخ
                <input name="rate" type="number" min="0" step="0.000001" value="{{ old('rate') }}" required>
            </label>
            <label>شروع اعتبار
                <input name="effective_from" type="date" value="{{ old('effective_from', now()->toDateString()) }}" required>
            </label>
            <label>پایان اعتبار (اختیاری)
                <input name="effective_to" type="date" value="{{ old('effective_to') }}">
            </label>
        </div>
        <button class="btn" type="submit" style="margin-top:16px">ثبت نرخ دستمزد</button>
    </form>
</div>

<div class="panel">
    <h2 style="margin-top:0">سوابق نرخ دستمزد</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>نوع دستمزد</th><th>مبلغ</th><th>شروع اعتبار</th><th>پایان اعتبار</th><th>وضعیت زمانی</th></tr></thead>
            <tbody>
                @forelse($rates as $rate)
                    <tr>
                        <td>{{ $rate->rate_type === 'hourly' ? 'ساعتی' : 'به‌ازای هر واحد تولید' }}</td>
                        <td>{{ number_format((float) $rate->rate, 2) }}</td>
                        <td>{{ $rate->effective_from }}</td>
                        <td>{{ $rate->effective_to ?: 'بدون تاریخ پایان' }}</td>
                        <td>
                            @if($rate->effective_from <= now()->toDateString() && (!$rate->effective_to || $rate->effective_to >= now()->toDateString()))
                                معتبر امروز
                            @elseif($rate->effective_from > now()->toDateString())
                                آینده
                            @else
                                پایان‌یافته
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:24px">برای این پرسنل هنوز نرخی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
