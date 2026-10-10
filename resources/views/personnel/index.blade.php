@extends('layouts.dashboard-shell')

@section('title', 'پرسنل')

@section('content')
<div class="page-heading">
    <div>
        <h1>پرسنل</h1>
        <p>مدیریت اطلاعات و وضعیت پرسنل مجموعه</p>
    </div>
</div>

@if(session('success'))
    <div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">
        {{ session('success') }}
    </div>
@endif

<div class="actions">
    <a class="btn" href="{{ route('users.index') }}">مدیریت کاربران</a>
    <a class="btn" href="{{ route('roles.index') }}">نقش‌ها و دسترسی‌ها</a>
</div>

<div class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>کد پرسنلی</th>
                    <th>نام</th>
                    <th>عنوان شغلی</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personnel as $p)
                    <tr>
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->job_title ?: '—' }}</td>
                        <td>
                            <span class="status {{ $p->is_active ? 'active' : 'inactive' }}">
                                {{ $p->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions" style="margin:0">
                                @if($p->user)
                                    <a class="btn" href="{{ route('users.edit', $p->user) }}">ویرایش کاربر</a>
                                @endif
                                @if($p->is_active)
                                    <form method="POST" action="{{ route('personnel.deactivate', $p) }}" onsubmit="return confirm('از غیرفعال‌کردن این پرسنل مطمئن هستید؟')">
                                        @csrf
                                        <button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">غیرفعال‌کردن</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px;color:#71879d">هنوز پرسنلی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($personnel, 'links'))
        <div class="pagination">{{ $personnel->links() }}</div>
    @endif
</div>
@endsection
