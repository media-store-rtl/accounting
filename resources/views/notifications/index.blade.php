@extends('layouts.dashboard-shell')

@section('title', 'مرکز اعلان‌ها')

@section('content')
@php
    $eventLabels = [
        'order.production_required' => [
            'title' => 'این سفارش به بررسی و برنامه‌ریزی تولید نیاز دارد',
            'message' => 'موجودی انبار برای تأمین کامل سفارش کافی نیست. سرپرست تولید باید امکان تولید و زمان‌بندی آن را بررسی کند.',
        ],
        'order.ready_for_delivery' => [
            'title' => 'سفارش آماده تحویل است',
            'message' => 'موجودی لازم برای سفارش فراهم است. واحد فروش می‌تواند مراحل درخواست تحویل را پیگیری کند.',
        ],
        'order.production_due_set' => [
            'title' => 'زمان آماده‌شدن سفارش در تولید مشخص شد',
            'message' => 'سرپرست تولید زمان قابل تحویل را ثبت کرده است. واحد فروش باید زمان را بررسی و در صورت نیاز با مشتری هماهنگ کند.',
        ],
        'delivery.requested' => [
            'title' => 'برای سفارش درخواست تحویل ثبت شد',
            'message' => 'درخواست تحویل ثبت شده و برای بررسی و انجام مراحل تحویل در اختیار واحد انبار قرار گرفته است.',
        ],
        'supply.stock_available' => [
            'title' => 'موجودی مواد برای تولید کافی است',
            'message' => 'انبار بررسی را انجام داده و موجودی کافی اعلام کرده است؛ درخواست آماده هماهنگی برای تحویل است.',
        ],
        'supply.stock_rejected' => [
            'title' => 'درخواست مواد توسط انبار رد شد',
            'message' => 'انبار درخواست مواد را رد کرده است. دلیل ثبت‌شده توسط انبار را بررسی کنید.',
        ],
        'supply.purchased' => [
            'title' => 'مواد درخواست‌شده خریداری شد',
            'message' => 'تدارکات وضعیت درخواست مواد را «خریداری شده» ثبت کرده است؛ برای دریافت مواد با انبار هماهنگ کنید.',
        ],
        'supply.stock_check_required' => [
            'title' => 'درخواست مواد اولیه در انتظار بررسی انبار است',
            'message' => 'تولید این مواد را درخواست کرده است. انباردار باید موجودی را بررسی و نتیجه را در صفحه درخواست مواد ثبت کند؛ تا پیش از ثبت نتیجه، اعلانی برای تدارکات ارسال نمی‌شود.',
        ],
        'supply.shortage' => [
            'title' => 'کسری مواد اولیه توسط انبار تأیید شد',
            'message' => 'انبار موجودی را بررسی کرده و کسری زیر را تأیید کرده است. تدارکات باید مقدار کسری را برای تاریخ نیاز اعلام‌شده تأمین کند.',
        ],
        'inventory.finished_goods_low' => [
            'title' => 'موجودی محصول نهایی کم است',
            'message' => 'موجودی محصول نهایی به نقطه سفارش رسیده یا از آن کمتر شده است. سرپرست تولید باید برنامه تولید و امکان جبران کمبود را بررسی کند.',
        ],
        'inventory.raw_material_low' => [
            'title' => 'موجودی ماده اولیه کم است',
            'message' => 'موجودی ماده اولیه به نقطه سفارش رسیده یا از آن کمتر شده است. واحد تدارکات باید تأمین مجدد را پیگیری کند.',
        ],
        'inventory_reorder_point_reached' => [
            'title' => 'موجودی کالا به نقطه سفارش رسیده است',
            'message' => 'موجودی این کالا به حد سفارش یا کمتر از آن رسیده است. واحد مسئول تأمین باید نیاز به تأمین مجدد را بررسی کند.',
        ],
        'purchase.received_financial_value' => [
            'title' => 'اطلاعات مالی خرید برای پیگیری ثبت شد',
            'message' => 'اطلاعات مبلغ خرید و هزینه‌های مستقیم ثبت شده است. مسئول مالی باید اطلاعات را بررسی و پیگیری کند.',
        ],
    ];

    $fieldLabels = [
        'order_id' => 'شماره سفارش',
        'delivery_request_id' => 'شماره درخواست تحویل',
        'supply_request_id' => 'شماره درخواست مواد اولیه',
        'production_id' => 'شماره دستور تولید',
        'goods_name' => 'نام کالا',
        'requested_quantity' => 'مقدار درخواستی',
        'available_quantity' => 'مقدار قابل تأمین از انبار',
        'shortage_quantity' => 'مقدار کسری تأییدشده',
        'needed_at' => 'تاریخ موردنیاز',
        'warehouse_note' => 'یادداشت انباردار',
        'purchase_id' => 'شماره خرید',
        'receipt_id' => 'شماره رسید خرید',
        'production_due_at' => 'زمان قابل تحویل اعلام‌شده توسط تولید',
        'purchase_total_amount' => 'مبلغ خرید',
        'direct_cost_total' => 'مجموع هزینه‌های مستقیم',
        'location_id' => 'انبار',
        'goods_id' => 'کالا',
        'current_quantity' => 'موجودی فعلی',
        'reorder_point' => 'نقطه سفارش',
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
                    'message' => 'رویدادی در مجموعه ثبت شده است. برای اطلاع از جزئیات، از مسئول مربوط به این فرآیند راهنمایی بگیرید.',
                ];
                $orderId = isset($data['order_id']) && is_numeric($data['order_id']) ? (int) $data['order_id'] : null;
                $canViewOrder = $orderId && auth()->user()->hasCompanyPermission((int) session('company_id'), 'order.view');
                $visibleFields = [
                    'order.production_required' => ['order_id'],
                    'order.ready_for_delivery' => ['order_id'],
                    'order.production_due_set' => ['order_id', 'production_due_at'],
                    'delivery.requested' => ['order_id', 'delivery_request_id'],
                    'supply.stock_check_required' => ['supply_request_id'],
                    'supply.shortage' => ['supply_request_id', 'production_id', 'order_id', 'goods_name', 'requested_quantity', 'available_quantity', 'shortage_quantity', 'needed_at', 'warehouse_note'],
                    'inventory.finished_goods_low' => ['goods_name', 'goods_id', 'location_id', 'current_quantity', 'reorder_point'],
                    'inventory.raw_material_low' => ['goods_name', 'goods_id', 'location_id', 'current_quantity', 'reorder_point'],
                    'inventory_reorder_point_reached' => ['goods_name', 'goods_id', 'location_id', 'current_quantity', 'reorder_point'],
                    'purchase.received_financial_value' => ['purchase_id', 'receipt_id', 'purchase_total_amount', 'direct_cost_total'],
                ];
                $visibleKeys = $visibleFields[$event] ?? [];
                $extraData = array_intersect_key($data, array_flip($visibleKeys));
                $companyId = (int) session('company_id');
                $goodsName = null;
                $locationName = null;

                if (in_array($event, ['inventory.finished_goods_low', 'inventory.raw_material_low', 'inventory_reorder_point_reached'], true) && $companyId > 0) {
                    if (!empty($data['goods_id'])) {
                        $goodsName = \Illuminate\Support\Facades\DB::table('goods')
                            ->where('company_id', $companyId)
                            ->where('id', (int) $data['goods_id'])
                            ->value('name');
                    }
                    if (!empty($data['location_id'])) {
                        $locationName = \Illuminate\Support\Facades\DB::table('locations')
                            ->where('company_id', $companyId)
                            ->where('id', (int) $data['location_id'])
                            ->value('name');
                    }
                }
            @endphp
            <article style="padding:15px;border:1px solid {{ $n->read_at ? '#1b354b' : '#246451' }};border-radius:12px;background:{{ $n->read_at ? '#0d2235' : '#0c282b' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
                    <strong style="font-size:13px;line-height:1.8;{{ $n->read_at ? 'color:#c2d1de' : 'color:#8ce8ce' }}">
                        {{ $presentation['title'] }}
                    </strong>
                    <small style="font-size:10px;color:#8198ac"><time class="notification-jalali-datetime" datetime="{{ \Illuminate\Support\Carbon::parse($n->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($n->created_at)->format('Y/m/d H:i') }}</time></small>
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
                @if(in_array($event, ['supply.stock_check_required', 'supply.shortage'], true) && auth()->user()->hasCompanyPermission((int) session('company_id'), 'supply_request.view'))
                    <div style="margin-top:10px"><a href="{{ route('workflows.supply') }}" style="color:#6ee7d0;text-decoration:none;font-size:11px">رفتن به درخواست‌های مواد اولیه ←</a></div>
                @endif

                @if(count($extraData) > 0)
                    <div style="display:flex;flex-direction:column;gap:5px;margin-top:10px;color:#9eb2c4;font-size:11px;line-height:1.9;overflow-wrap:anywhere">
                        @foreach($extraData as $k => $v)
                            @if($k !== 'order_id')
                                <div>
                                    <span style="color:#71879d">{{ $fieldLabels[$k] ?? 'جزئیات' }}:</span>
                                    <strong style="color:#e9f3fb">
                                        @if($k === 'goods_id')
                                            {{ $goodsName ?: 'کالای ثبت‌شده' }}
                                        @elseif($k === 'location_id')
                                            {{ $locationName ?: 'انبار ثبت‌شده' }}
                                        @elseif($k === 'production_due_at' && is_scalar($v) && $v !== '')
                                            <time class="notification-jalali-datetime" datetime="{{ \Illuminate\Support\Carbon::parse($v)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($v)->format('Y/m/d H:i') }}</time>
                                        @elseif(is_numeric($v) && in_array($k, ['purchase_total_amount', 'direct_cost_total'], true))
                                            {{ number_format((float) $v, 0) . ' ریال' }}
                                        @elseif(is_scalar($v))
                                            {{ $v }}
                                        @endif
                                    </strong>
                                </div>
                            @endif
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
                                    <small style="color:#8198ac"><time class="notification-jalali-datetime" datetime="{{ \Illuminate\Support\Carbon::parse($reader->read_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($reader->read_at)->format('Y/m/d H:i') }}</time></small>
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

<script>
document.querySelectorAll('.notification-jalali-datetime').forEach(function (element) {
    const raw = element.getAttribute('datetime');
    if (!raw) return;
    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) return;

    element.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).format(date);
});
</script>

@endsection
