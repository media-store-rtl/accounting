@extends('layouts.dashboard-shell')

@section('title', 'کاربران')

@section('content')
<div class="page-heading">
    <div><h1>کاربران و دسترسی کاربران</h1><p>مدیریت حساب‌های کاربری و وضعیت دسترسی در مجموعه</p></div>
</div>
@if(session('success'))
    <div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>
@endif
<div class="actions">
    <a class="btn primary" href="{{ route('users.create') }}">+ کاربر جدید</a>
    <a class="btn" href="{{ route('roles.index') }}">مدیریت نقش‌ها</a>
    <a class="btn" href="{{ route('personnel.index') }}">پرسنل</a>
</div>
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>وضعیت</th><th>عملیات</th></tr></thead>
      <tbody>
      @forelse($users as $u)
        <tr>
          <td>{{ $u->name }} @if($u->isAccountOwner()) <span class="status active">مالک حساب</span> @endif</td>
          <td>{{ $u->email }}</td>
          <td>{{ $u->roles->pluck('name')->join('، ') ?: 'بدون نقش' }}</td>
          <td><span class="status {{ $u->is_active && $u->pivot->is_active ? 'active' : 'inactive' }}">{{ $u->is_active && $u->pivot->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
          <td><div class="actions" style="margin:0">
            @if(!$u->isAccountOwner())
              <a class="btn" href="{{ route('users.edit',$u) }}">ویرایش</a>
              <a class="btn" href="{{ route('users.access',$u) }}">دسترسی</a>
              @if($u->is_active)
                <form method="POST" action="{{ route('users.deactivate',$u) }}" onsubmit="return confirm('از غیرفعال‌کردن این کاربر مطمئن هستید؟')">@csrf<button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">غیرفعال</button></form>
              @else
                <form method="POST" action="{{ route('users.activate',$u) }}" onsubmit="return confirm('این کاربر فعال شود؟')">@csrf<button class="btn" type="submit">فعال‌کردن</button></form>
              @endif
            @endif
          </div></td>
        </tr>
      @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:#71879d">کاربری برای نمایش وجود ندارد.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($users, 'links'))<div class="pagination">{{ $users->links() }}</div>@endif
</div>
@endsection
