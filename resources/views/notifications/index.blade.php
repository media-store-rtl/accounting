@extends('layouts.dashboard-shell')

@section('title', 'مرکز اعلان‌ها')

@section('content')
@php
    $eventLabels = [
        'order.production_required' => [
            'title' => 'این سفارش به تولید نیاز دارد',
            'message' => 'موجودی انبار برای تأمین کامل این سفارش کافی نیست. لطفاً جزئیات سفارش را بررسی کنید و برای برنامه‌ریزی تولید اقدام کنید.',
        ],
        'order.ready_for_delivery' => [
            'title' => 'این سفارش آماده تحویل است',
            'message' => 'وضعیت سفارش بررسی شده و برای پیگیری مرحله تحویل آماده است.',
        ],
    ];
    $fieldLabels = [
        'order_id' => 'شماره سفارش',
        'company_id' => 'شناسه مجموعه',
    ];
@endphp

<div class="page-heading">
    <div>
        <h1>مرکز اعلان‌ها</h1>
        <p>پیام‌های مربوط به کارها و سفارش‌هایی که نیاز به پیگیری دارند</p>
    </div>
</div>

<div class="panel">
    <div style="display:flex;flex-direction:column;gap:10px">
        @forelse($rows as $n)
            @php
                $data = json_decode($n->data, true) ?: [];
                $event = $data['event'] ?? $n->type;
                $presentation = $eventLabels[$event] ?? [
                    'title' => 'اعلان جدید',
                    'message' => 'یک رویداد در مجموعه ثبت شده است. برای جزئیات بیشتر اطلاعات زیر را بررسی کنید.',
                ];
                $orderId = isset($data['order_id']) && is_numeric($data['order_id']) ? (int) $data['order_id'] : null;
                $canViewOrder = $orderId && auth()->user()->hasCompanyPermission((int) session('company_id'), 'order.view');
            @endphp
            <article style="padding:15px;border:1px solid {{ $n->read_at ? '#1b354b' : '#246451' }};border-radius:12px;background:{{ $n->read_at ? '#0d2235' : '#0c282b' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                    <strong style="font-size:13px;line-height:1.8;{{ $n->read_at ? 'color:#c2d1de' : 'color:#8ce8ce' }}">
                        {{ $presentation['title'] }}
                    </strong>
                    <small style="font-size:10px;color:#8198ac">{{ \Illuminate\Support\Carbon::parse($n->created_at)->format('Y/m/d H:i') }}</small>
                </div>

                <p style="margin:9px 0 0;color:#c2d1de;font-size:11px;line-height:2">{{ $presentation['message'] }}</p>

                @if($orderId)
                    <div style="margin-top:10px;color:#9eb2c4;font-size:11px">
                        شماره سفارش: <strong style="color:#e9f3fb">#{{ $orderId }}</strong>
                        @if($canViewOrder)
                            <a href="{{ route('sales.orders.show', $orderId) }}" style="margin-right:10px;color:#6ee7d0;text-decoration:none">مشاهده سفارش ←</a>
                        @endif
                    </div>
                @endif

                @php
                    $extraData = array_diff_key($data, array_flip(['event', 'notification_batch_id', 'order_id', 'company_id']));
                @endphp
                @if(count($extraData) > 0)
                    <div style="display:flex;flex-direction:column;gap:5px;margin-top:10px;color:#9eb2c4;font-size:11px;line-height:1.9;overflow-wrap:anywhere">
                        @foreach($extraData as $k => $v)
                            <div><span style="color:#71879d">{{ $fieldLabels[$k] ?? $k }}:</span> {{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</div>
                        @endforeach
                    </div>
                @endif

                @if($n->readers->isNotEmpty())
                    <div style="margin-top:12px;padding:10px 12px;border:1px solid #245447;border-radius:9px;background:#0a2024">
                        <strong style="display:block;color:#8ce8ce;font-size:11px;margin-bottom:7px">خوانده‌شده توسط</strong>
                        <div style="display:flex;flex-direction:column;gap:6px">
                            @foreach($n->readers as $reader)
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;font-size:11px">
                                    <span style="color:#d2e5ee">{{ $reader->username ?: $reader->user_name }}</span>
                                    <small style="color:#8198ac">{{ \Illuminate\Support\Carbon::parse($reader->read_at)->format('Y/m/d H:i') }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif(!empty($data['notification_batch_id']))
                    <div style="margin-top:10px;color:#8198ac;font-size:10px">هنوز هیچ‌یک از گیرندگان این اعلان را نخوانده است.</div>
                @endif

                @if(!$n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" style="margin-top:12px">
                        @csrf
                        <button type="submit" class="btn">متوجه شدم؛ علامت‌گذاری به‌عنوان خوانده‌شده</button>
                    </form>
                @else
                    <div style="margin-top:10px;color:#8198ac;font-size:10px">این اعلان را خوانده‌اید.</div>
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
