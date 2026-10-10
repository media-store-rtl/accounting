@extends('layouts.dashboard-shell')

@section('title', 'پشتیبان‌گیری و بازیابی')

@section('content')
<div class="page-heading">
    <div>
        <h1>پشتیبان‌گیری و بازیابی</h1>
        <p>ساخت، بارگذاری، دریافت و بازیابی نسخه‌های پشتیبان اطلاعات</p>
    </div>
    <form method="POST" action="{{ route('backups.create') }}" style="margin:0">
        @csrf
        <button type="submit" class="btn primary">＋ ایجاد نسخه پشتیبان</button>
    </form>
</div>

@if(session('success'))
    <div class="panel" role="status" style="margin-bottom:16px;border-color:#246451;color:#8ce8ce">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="panel" role="alert" style="margin-bottom:16px;border-color:#704047;color:#ffb8a8">
        <strong>عملیات انجام نشد:</strong>
        <ul style="margin:8px 0 0;padding-right:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="margin-bottom:18px">
    <div style="margin-bottom:12px">
        <h2 style="font-size:15px;margin:0 0 6px">بارگذاری نسخه پشتیبان</h2>
        <p style="font-size:11px;color:#8198ac;margin:0">فایل پشتیبان موجود را برای ثبت در فهرست انتخاب کنید.</p>
    </div>
    <form method="POST" action="{{ route('backups.upload') }}" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        @csrf
        <label style="display:block;flex:1;min-width:220px;font-size:11px;color:#9eb2c4">
            فایل پشتیبان
            <input type="file" name="backup" required style="display:block;width:100%;margin-top:8px;padding:10px;border:1px solid #29465e;border-radius:9px;background:#0d2235;color:#dcebf5;font:inherit">
        </label>
        <button type="submit" class="btn">بارگذاری فایل</button>
    </form>
</div>

<div class="panel">
    <div class="page-heading" style="margin-bottom:14px">
        <div>
            <h2 style="font-size:16px;margin:0">نسخه‌های پشتیبان</h2>
            <p>برای بازیابی، پیش از تأیید از مناسب بودن فایل انتخاب‌شده اطمینان حاصل کنید.</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>نام فایل</th>
                    <th>تاریخ ایجاد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>{{ $backup->original_name }}</td>
                        <td>{{ $backup->created_at }}</td>
                        <td>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                <a class="btn" href="{{ route('backups.download', $backup) }}">↓ دانلود</a>
                                <form method="POST" action="{{ route('backups.restore', $backup) }}" style="margin:0">
                                    @csrf
                                    <input type="hidden" name="confirmation" value="RESTORE">
                                    <button type="submit" class="btn" style="border-color:#704047;color:#ffb8a8" onclick="return confirm('بازیابی ممکن است اطلاعات جاری را تغییر دهد. قبل از ادامه مطمئن شوید نسخه پشتیبان درستی انتخاب شده است. ادامه می‌دهید؟')">بازیابی</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;color:#8198ac;padding:28px">هنوز نسخه پشتیبانی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($backups, 'links'))
        <div class="pagination">{{ $backups->links() }}</div>
    @endif
</div>
@endsection
