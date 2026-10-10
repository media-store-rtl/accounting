@extends('layouts.dashboard-shell')

@section('title', 'مرکز اعلان‌ها')

@section('content')
<div class="page-heading">
    <div>
        <h1>مرکز اعلان‌ها</h1>
        <p>مشاهده رویدادها و پیگیری اعلان‌های ثبت‌شده در مجموعه</p>
    </div>
</div>

<div class="panel">
    <div style="display:flex;flex-direction:column;gap:10px">
        @forelse($rows as $n)
            @php $data = json_decode($n->data, true) ?: []; @endphp
            <article style="padding:15px;border:1px solid {{ $n->read_at ? '#1b354b' : '#246451' }};border-radius:12px;background:{{ $n->read_at ? '#0d2235' : '#0c282b' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                    <strong style="font-size:12px;line-height:1.8;{{ $n->read_at ? 'color:#c2d1de' : 'color:#8ce8ce' }}">
                        {{ $data['event'] ?? $n->type }}
                    </strong>
                    <small style="font-size:10px;color:#8198ac">{{ $n->created_at }}</small>
                </div>

                @if(count(array_diff(array_keys($data), ['event'])) > 0)
                    <div style="display:flex;flex-direction:column;gap:5px;margin-top:10px;color:#9eb2c4;font-size:11px;line-height:1.9;overflow-wrap:anywhere">
                        @foreach($data as $k => $v)
                            @if($k !== 'event')
                                <div><span style="color:#71879d">{{ $k }}:</span> {{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(!$n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="margin-top:12px">
                        @csrf
                        <button type="submit" class="btn">علامت‌گذاری به‌عنوان خوانده‌شده</button>
                    </form>
                @endif
            </article>
        @empty
            <div style="text-align:center;padding:30px 16px;color:#8198ac;font-size:12px">
                اعلانی برای نمایش وجود ندارد.
            </div>
        @endforelse
    </div>

    @if(method_exists($rows, 'links'))
        <div class="pagination">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
