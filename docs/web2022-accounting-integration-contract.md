# قرارداد یکپارچه‌سازی Web2022 و Accounting/Provisioning

## ۱. هدف و وضعیت تصمیم‌ها

این سند قرارداد پیشنهادی ارتباط Backend وب‌سایت Web2022 با قابلیت Provisioning درون پروژه Accounting را برای خرید و چرخه عمر اشتراک تعریف می‌کند. این سند طراحی مستنداتی است و endpoint، جدول، migration، job، webhook، credential یا رفتار runtime ایجاد نمی‌کند.

- **تثبیت‌شده در معماری:** Web2022 مالک checkout، پرداخت، سفارش و وضعیت تجاری اشتراک است؛ Accounting/Provisioning مالک Registry canonical مشتری/کسب‌وکار/tenant، دریافت رویداد و چرخه عمر عملیاتی tenant است؛ Dolibarr مالک کاربران، نقش‌ها، مجوزها و عملیات ERP هر tenant است.
- **الزام معماری:** رویداد خرید فقط پس از commit قطعی تراکنش تجاری منتشر می‌شود؛ Outbox در Web2022 و Inbox در Accounting استفاده می‌شوند؛ تحویل حداقل یک‌بار فرض می‌شود و مصرف‌کننده باید idempotent باشد.
- **پیشنهاد این سند:** envelope نسخه‌بندی‌شده، snapshot وضعیت اشتراک در هر رویداد lifecycle، کنترل ترتیب با aggregate version، پاسخ HTTP پذیرش غیرهم‌زمان و مسیر reconciliation.
- **تصمیم‌های نیازمند تصویب:** روش احراز هویت و امضا، transport نهایی، نام/نوع دقیق رویدادها، semantics لغو و تعلیق، قواعد refund/chargeback، status codeها، سیاست out-of-order و زمان‌بندی reconciliation.

## ۲. مرز مالکیت و شناسه‌ها

| مفهوم | مالک و مرجع | قاعده |
|---|---|---|
| Customer | Accounting/Provisioning | تولید و نگهداری `customer_id` canonical |
| Business | Accounting/Provisioning | تولید و نگهداری `business_id` canonical؛ هر Business متعلق به یک Customer |
| Tenant | Accounting/Provisioning | تولید و نگهداری `tenant_id` و وضعیت عملیاتی |
| Subscription | Web2022 | تولید و مالکیت `subscription_id` و وضعیت تجاری |
| Order / Payment | Web2022 | شناسه opaque؛ منبع حقیقت مالی در Web2022 |
| کاربران/نقش‌های عملیاتی | Dolibarr | Provisioning رمز یا حساب روزمره کاربران را مدیریت نمی‌کند |

قواعد:

1. شناسه‌های canonical مشتری، کسب‌وکار و tenant فقط توسط Accounting/Provisioning تولید می‌شوند.
2. Web2022 شناسه‌های canonical برگشتی را نگهداری می‌کند و در event می‌فرستد؛ Accounting نباید از email، موبایل، شناسه داخلی Laravel یا شماره سفارش برای ساخت هویت canonical استفاده کند.
3. `subscription_id` را Web2022 تولید می‌کند؛ Accounting آن را به Business و Tenant نگاشت می‌کند.
4. یک Customer می‌تواند چند Business داشته باشد؛ هر Business اشتراک و Tenant مستقل دارد. رویداد Business A نباید Business B را تغییر دهد.
5. Business می‌تواند پیش از خرید ایجاد شود؛ Tenant فقط پس از رویداد فعال‌سازی معتبر ایجاد می‌شود.
6. Accounting باید رابطه و مالکیت customer/business/subscription را در Registry خودش اعتبارسنجی کند؛ payload به‌تنهایی مجوز دسترسی نیست.

## ۳. منبع حقیقت و مدل وضعیت

### ۳.۱. تفکیک وضعیت‌ها

- **وضعیت تجاری اشتراک:** Web2022 مرجع نهایی آن است؛ شامل پلن، دوره اعتبار، پرداخت و تصمیم‌های تجاری مانند تمدید، لغو یا تعلیق.
- **وضعیت عملیاتی tenant:** Accounting/Provisioning مرجع آن است؛ مانند `not_created`, `provisioning`, `ready`, `provisioning_failed`, `maintenance` و `suspended`.

پرداخت موفق به معنی آماده‌بودن Tenant نیست. پذیرش event به معنی اتمام provisioning نیست. تمدید تجاری نباید Tenant یا DB جدید بسازد.

### ۳.۲. Entitlement

پیشنهاد پایه برای تصویب:

- Web2022 مرجع نهایی تصمیم تجاری و entitlement است.
- Accounting یک projection نسخه‌بندی‌شده و قابل audit از آخرین وضعیت دریافتی نگه می‌دارد؛ این projection منبع حقیقت مالی/تجاری مستقل نیست.
- Accounting بر اساس projection معتبر، عملیات provisioning و lifecycle عملیاتی را هماهنگ می‌کند.
- Dolibarr یا لایه enforcement مورد تأیید، محدودیت دسترسی را در زمان اجرا اعمال می‌کند؛ این قرارداد enforcement را پیاده‌سازی نمی‌کند.

**نیازمند تصویب:** آیا Accounting در chargeback، خطای اعتبارسنجی یا اختلاف نسخه می‌تواند موقتاً fail-closed کند؟ پیشنهاد ایمن این است که event نامعتبر/نامعلوم هیچ دسترسی جدیدی اعطا نکند و به reconciliation برود؛ تصمیم تجاری نهایی همچنان با Web2022 باشد.

### ۳.۳. نگاشت رویداد به اقدام

| رویداد/وضعیت | اقدام مورد انتظار Accounting | موضوع باز |
|---|---|---|
| فعال‌سازی معتبر | ایجاد Tenant در خرید اول؛ در غیر این صورت resolve همان Business/Tenant | مدل پایه: یک Tenant برای هر Business |
| تمدید معتبر | به‌روزرسانی projection و حفظ همان Tenant/DB | تغییر پلن و ارتقای منابع |
| انقضای عادی | ثبت انقضا؛ حفظ Tenant و داده؛ read-only طبق سیاست محصول | enforcement خارج از این سند |
| تعلیق | ثبت نوع و زمان اثر؛ اقدام متناسب با policy | تفکیک تعلیق تجاری از امنیتی/اداری |
| ازسرگیری | بازگشت وضعیت تجاری معتبر؛ حفظ همان Tenant | بازیابی Tenant خراب/حذف‌شده |
| لغو | ثبت لغو و تاریخ اثر؛ عدم حذف خودکار Tenant | پایان‌دوره یا فوری |
| بازپرداخت | ثبت دامنه refund و مرجع مالی | جزئی/کامل و اثر entitlement |
| chargeback | ثبت رخداد و ارجاع به policy ریسک | وضعیت باز/نهایی و زمان اثر |

شروع‌نشده، تعلیق امنیتی/اداری و وضعیت نامشخص یا اعتبارسنجی ناموفق از انقضای عادی read-only متمایزند. semantics دقیق تجاری هنوز نیازمند تصویب است.

## ۴. انتقال رویداد و تضمین تحویل

### ۴.۱. جریان پیشنهادی

1. Backend Web2022 تغییر تجاری را در تراکنش خود ثبت می‌کند.
2. در همان مرز تراکنشی، Outbox record با event ID یکتا ایجاد می‌کند؛ event پیش از commit منتشر نمی‌شود.
3. dispatcher رویداد را با retry به endpoint داخلی Accounting می‌فرستد.
4. Accounting هویت caller، تازگی/امضا، schema، شناسه‌ها و aggregate version را اعتبارسنجی می‌کند.
5. Accounting رویداد را در Inbox با قید یکتایی event ID ثبت و پاسخ پذیرش می‌دهد.
6. پردازش دامنه و provisioning پس از پذیرش HTTP انجام می‌شود؛ ساخت Tenant نباید در پاسخ HTTP موفق فرض شود.
7. Accounting وضعیت پردازش و provisioning را ثبت می‌کند و نتیجه قابل استعلام فراهم می‌سازد.
8. Outbox تا دریافت پاسخ پذیرش معتبر یا رسیدن به سیاست خطای نهایی، ارسال مجدد می‌کند.
9. reconciliation اختلاف وضعیت Web2022 و projection در Accounting را شناسایی می‌کند.

تحویل حداقل یک‌بار فرض می‌شود؛ exactly-once در شبکه وعده داده نمی‌شود. اثر business-level با Inbox، idempotency و قیود پایدار تکرارناپذیر می‌شود.

### ۴.۲. Transport و endpoint

پیشنهاد اولیه HTTP push از Backend Web2022 به API نسخه‌بندی‌شده Accounting است. مسیر مفهومی: `POST /api/internal/v1/events`.

این مسیر نمونه طراحی است، نه endpoint موجود یا تصویب‌شده. انتخاب نهایی HTTP push در برابر transport دیگر، URL، timeout و retry باید در ADR تصویب شود. مرورگر مشتری نباید endpoint داخلی را فراخوانی کند.

### ۴.۳. پاسخ پذیرش

پاسخ موفق پیشنهادی:

```json
{
  "accepted": true,
  "event_id": "uuid",
  "inbox_id": "accounting-inbox-id",
  "processing_status": "accepted",
  "correlation_id": "uuid"
}
```

این پاسخ فقط ثبت معتبر event را تأیید می‌کند، نه آماده‌شدن Tenant.

Status codeهای پیشنهادی (نیازمند تصویب و contract test):

- `202 Accepted`: event جدید در Inbox ثبت و غیرهم‌زمان پردازش می‌شود.
- `200 OK`: همان event ID قبلاً با payload یکسان پذیرفته شده است.
- `400`: JSON/schema نامعتبر.
- `401/403`: caller احراز نشده یا مجاز نیست.
- `409`: event ID یکسان با payload متفاوت یا تعارض نیازمند reconciliation.
- `422`: ارجاع Customer/Business/Subscription ناسازگار با قواعد دامنه.
- `429`: محدودیت نرخ؛ retry با backoff.
- `5xx`: خطای موقت دریافت؛ Outbox ارسال مجدد کند.

خطای HTTP نباید باعث ساخت سفارش یا شارژ مجدد شود.

## ۵. Event envelope نسخه‌بندی‌شده

### ۵.۱. نمونه مفهومی

```json
{
  "event_id": "8f21f91e-2d64-4d83-a7c8-21c4f36d21ab",
  "event_type": "subscription.activated",
  "schema_version": 1,
  "occurred_at": "2026-11-14T10:00:00Z",
  "producer": "web2022",
  "correlation_id": "c6e1b4c0-2f9d-4ef3-9c9f-3f4e72b1d431",
  "aggregate": {
    "type": "subscription",
    "id": "web2022-subscription-reference",
    "version": 1
  },
  "customer_id": "accounting-customer-uuid",
  "business_id": "accounting-business-uuid",
  "subscription": {
    "subscription_id": "web2022-subscription-reference",
    "status": "active",
    "plan_code": "stable-plan-code",
    "period": {
      "starts_at": "2026-11-14T10:00:00Z",
      "ends_at": "2027-11-14T10:00:00Z"
    }
  },
  "references": {
    "order_id": "opaque-web2022-order-reference",
    "payment_id": "opaque-web2022-payment-reference"
  },
  "change": {
    "reason_code": "initial_purchase",
    "effective_at": "2026-11-14T10:00:00Z"
  }
}
```

مقادیر نمونه ساختگی‌اند. زمان‌ها در wire format به UTC و ISO 8601 هستند؛ تبدیل به تقویم جلالی فقط در UI انجام می‌شود. شناسه‌های سفارش/پرداخت opaque هستند و نباید اطلاعات کارت، token درگاه، رمز یا secret حمل کنند.

### ۵.۲. قواعد فیلدها

- `event_id`: UUID یکتا و immutable.
- `event_type`: نام رویداد از فهرست نسخه‌بندی‌شده.
- `schema_version`: تغییر ناسازگار نیازمند نسخه جدید.
- `occurred_at`: زمان رخداد در Web2022، نه زمان دریافت.
- `aggregate.id`: شناسه Subscription در Web2022؛ `aggregate.version` عدد صحیح افزایشی برای همان subscription.
- `customer_id` و `business_id`: شناسه‌های canonical صادرشده از Accounting.
- `subscription.subscription_id`: باید با `aggregate.id` برابر باشد.
- `status`, `plan_code`, `period`: snapshot وضعیت تجاری پس از تغییر؛ plan code باید پایدار و ماشینی باشد.
- `references`: مراجع سفارش/پرداخت در صورت وجود.
- `change.reason_code`: کد محدود و مستند، نه متن آزاد برای منطق ماشینی.

پیشنهاد می‌شود هر رویداد lifecycle علاوه بر نوع تغییر، snapshot کامل وضعیت فعلی Subscription را حمل کند تا consumer برای بازسازی وضعیت به ترتیب تحویل وابسته نباشد. این پیشنهاد باید با مدل واقعی Web2022 و privacy review تطبیق داده شود.

## ۶. رویدادهای چرخه عمر

نام‌های زیر نامزد هستند و semantics نهایی باید تصویب شود:

| Event type | زمان انتشار | اثر مورد انتظار در Accounting |
|---|---|---|
| `subscription.activated` | خرید اول پس از commit و فعال‌سازی تجاری | ایجاد/resolve projection؛ ایجاد Tenant در صورت نبود |
| `subscription.renewed` | تمدید پس از commit قطعی | به‌روزرسانی دوره؛ حفظ Tenant/DB |
| `subscription.expired` | رسیدن به پایان اعتبار طبق قاعده Web2022 | ثبت انقضا؛ حفظ Tenant و داده |
| `subscription.suspended` | تغییر به تعلیق | ثبت نوع و زمان اثر؛ اقدام مطابق policy |
| `subscription.resumed` | رفع تعلیق | به‌روزرسانی projection؛ حفظ Tenant |
| `subscription.cancelled` | لغو با زمان اثر مشخص | ثبت لغو؛ بدون حذف خودکار Tenant |
| `payment.refunded` | refund ثبت‌شده در Web2022 | ثبت مرجع و دامنه refund؛ اعمال policy مصوب |
| `payment.chargeback` | رخداد chargeback | ثبت رخداد و ارجاع به policy ریسک |

رویداد payment به‌تنهایی جایگزین snapshot اشتراک نیست. اگر refund یا chargeback وضعیت Subscription را تغییر می‌دهد، Web2022 باید تغییر subscription را نیز با aggregate version جدید منتشر کند یا رابطه این رویدادها را صریحاً تعریف کند.

موارد تجاری باز: لغو پایان‌دوره یا فوری؛ تعلیق تجاری در برابر امنیتی/اداری؛ refund جزئی/کامل و برگشت آن؛ chargeback باز/نهایی؛ تغییر پلن میانه دوره و prorating؛ منطقه زمانی و لحظه دقیق انقضا.

## ۷. Idempotency، ترتیب و پردازش تکراری

### ۷.۱. Event ID

- Accounting روی `event_id` قید یکتای پایدار دارد.
- تکرار همان event ID با payload hash یکسان، نتیجه قبلی را برمی‌گرداند و side effect جدید ایجاد نمی‌کند.
- همان event ID با payload hash متفاوت رد، audit و برای بررسی امنیتی علامت‌گذاری می‌شود.
- ثبت Inbox و ایجاد کار پردازش باید در مرز تراکنشی سازگار باشد تا crash بین پذیرش و enqueue موجب گم‌شدن event نشود.

### ۷.۲. Aggregate version

- Web2022 برای هر Subscription نسخه افزایشی تولید می‌کند.
- Accounting آخرین نسخه اعمال‌شده را نگه می‌دارد.
- نسخه پایین‌تر نباید snapshot جدید را overwrite کند.
- نسخه مساوی با event ID متفاوت نیازمند تشخیص duplicate معنایی یا conflict است.
- نسخه پرشی (مثلاً ۷ پس از ۵) نباید بی‌صدا پذیرفته شود؛ پیشنهاد: ثبت و انتقال به reconciliation، مگر snapshot کامل و قواعد مصوب اعمال امن نسخه را مجاز کنند.

### ۷.۳. Idempotency دامنه

- در مدل پایه برای هر Business یک Tenant هدف وجود دارد؛ ایجاد Tenant با قفل/قید پایدار در سطح Business محافظت می‌شود.
- activation تکراری نباید DB، Tenant یا سفارش جدید بسازد.
- renewal فقط projection/period را تغییر می‌دهد و Tenant/DB را حفظ می‌کند.
- retry job از resource referenceهای ثبت‌شده استفاده و پیش از ساخت مجدد هر منبع، وجود و مالکیت آن را بررسی می‌کند.
- provisioning retry هرگز به مسیر پرداخت یا شارژ کیف پول برنمی‌گردد.

## ۸. خطا، retry و dead-letter

### Web2022 / Outbox

- ارسال فقط پس از commit تجاری.
- retry برای timeout، network error، `429` و `5xx` با exponential backoff و jitter.
- پاسخ `2xx` معتبر، پیام را تحویل‌شده می‌کند؛ پذیرش غیرهم‌زمان به معنی provisioning آماده نیست.
- خطاهای دائمی `4xx` نباید با retry سریع تکرار شوند؛ پیام در وضعیت نیازمند بررسی بماند.
- Outbox تا ثبت نتیجه تحویل/پذیرش، event و مراجع را حفظ کند؛ retention باید تصویب شود.

### Accounting / Inbox

- خطای موقت پس از پذیرش باعث از دست‌رفتن event نمی‌شود؛ job قابل retry است.
- خطای schema، هویت، ownership یا تناقض دامنه به صف بررسی برود؛ در حالت مبهم Tenant ساخته نشود.
- پس از سقف retry، job به وضعیت exhausted/dead-letter با error code غیرحساس، correlation ID و audit برود.
- retry دستی فقط برای اپراتور مجاز، با دلیل ثبت‌شده و همان idempotency key انجام شود.
- statusهای پیشنهادی: `accepted`, `processing`, `processed`, `retry_scheduled`, `failed_permanent`, `needs_reconciliation`.

مقادیر backoff، سقف تلاش، retention، alert و سطح دسترسی اپراتور در runbook/ADR تعیین می‌شوند.

## ۹. استعلام وضعیت و reconciliation

Endpoint خواندنی پیشنهادی:

`GET /api/internal/v1/businesses/{business_id}/provisioning-status`

پاسخ مفهومی:

```json
{
  "business_id": "accounting-business-uuid",
  "tenant_id": "accounting-tenant-uuid",
  "subscription_id": "web2022-subscription-reference",
  "commercial_status_version": 8,
  "tenant_status": "provisioning",
  "last_event_id": "uuid",
  "last_event_status": "processed",
  "last_error_code": null,
  "updated_at": "2026-11-14T10:15:00Z",
  "correlation_id": "uuid"
}
```

این endpoint و پاسخ نمونه‌اند، نه API موجود. پاسخ نباید DB credentials، filesystem path خصوصی یا secret را افشا کند.

Reconciliation باید Outbox قدیمی، event پذیرفته‌شده بدون نتیجه، اختلاف aggregate version، اشتراک معتبر با Tenant آماده‌نشده، Tenant آماده با projection منقضی/تعلیق‌شده و resource ناقص/orphan را کشف کند. روش pull snapshot از Web2022، گزارش دوطرفه یا ترکیبی، cadence و مالک اجرا هنوز باز است. اصلاح خودکار نباید بدون audit وضعیت تجاری Web2022 را تغییر دهد یا پرداختی ایجاد کند.

## ۱۰. امنیت ارتباط

الزامات پایه:

- TLS؛ هویت سرویس Web2022 مستقل از حساب کاربر و session مرورگر؛ scope حداقلی؛ اعتبارسنجی مالکیت Customer/Business در Accounting.
- جلوگیری از replay با timestamp، nonce/event ID و پنجره زمانی مجاز.
- محدودیت اندازه body، schema validation، rate limiting و کنترل نسخه.
- عدم قرار دادن secret/token در URL، query string یا log.
- عدم استفاده مجدد از secret و SSO فعلی Laravel/Web2022 برای API جدید بدون طراحی و تصویب مستقل.
- پشتیبانی از key rotation، لغو credential و audit.
- عدم افشای stack trace، DB detail یا secret در پاسخ خطا.

**تصمیم باز:** HMAC با timestamp/nonce در برابر امضای نامتقارن یا روش دیگر. انتخاب باید با توجه به secret storage، rotation، replay protection، cPanel و عملیات کلیدها در ADR امنیتی انجام شود. این سند الگوریتم یا secretی را تصویب نمی‌کند.

## ۱۱. قرارداد سازگاری و تست دوطرفه

پیش از production، producer در Web2022 و consumer در Accounting باید contract test مشترک داشته باشند. حداقل سناریوها:

1. activation معتبر پس از commit → یک Inbox event و حداکثر یک Tenant.
2. event تکراری با payload یکسان → بدون side effect تکراری.
3. همان event ID با payload متفاوت → رد و audit.
4. renewal با نسخه بالاتر → حفظ Tenant/DB قبلی.
5. event قدیمی پس از نسخه جدید → عدم rollback projection.
6. نسخه پرشی/ترتیب نامعلوم → reconciliation طبق policy.
7. customer/business نامرتبط یا external reference ناشناخته → رد بدون افشای داده.
8. timeout پس از ثبت Inbox → retry producer نتیجه تکراری نسازد.
9. خطای موقت provisioning → retry بدون شارژ مجدد یا منابع تکراری.
10. expiry عادی → حفظ Tenant و داده؛ read-only جداگانه آزموده شود.
11. refund/chargeback/cancel/suspend/resume → مطابق semantics مصوب.
12. schema نامعتبر، فیلد اختیاری غایب یا نسخه پشتیبانی‌نشده → رفتار مستند.

Contract test باید هم روی نمونه payload و هم producer/consumer واقعی اجرا شود؛ تأیید سند جایگزین تست integration نیست.

## ۱۲. تصمیم‌های لازم پیش از پیاده‌سازی

| موضوع | پیشنهاد اولیه | وضعیت |
|---|---|---|
| مرجع وضعیت تجاری | Web2022 | مرز مالکیت تثبیت‌شده |
| Registry و شناسه‌های canonical | Accounting/Provisioning | مرز مالکیت تثبیت‌شده |
| transport | HTTP push با Outbox/Inbox | پیشنهاد؛ نیازمند تصویب |
| envelope | JSON نسخه‌بندی‌شده با snapshot و aggregate version | پیشنهاد؛ نیازمند تصویب |
| امضای درخواست | HMAC یا نامتقارن پس از ADR | باز |
| endpointها و status code | نمونه‌های این سند | باز |
| semantics لغو/تعلیق | تفکیک نوع و زمان اثر | باز |
| refund/chargeback | بدون حذف Tenant؛ اثر entitlement طبق policy | باز |
| ترتیب رویداد | aggregate version و reconciliation | پیشنهاد؛ نیازمند تصویب |
| retry/backoff/retention | policy عملیاتی جدا | باز |
| reconciliation | استعلام/گزارش دوطرفه | باز |
| worker/queue و زیرساخت | پس از ارزیابی runtime/cPanel | باز |
| SSO به Dolibarr | طراحی مستقل؛ قرارداد فعلی Laravel reuse فرض نشود | باز |

## ۱۳. خارج از محدوده و دروازه اجرا

این سند مجوز تغییر checkout، wallet، gateway verification، API، Outbox/Inbox runtime، migration، DB، worker، ساخت Tenant، SSO، read-only enforcement، cPanel، DNS، server، production، deployment یا حذف قابلیت‌های فعلی Accounting/Laravel نیست.

ترتیب پیشنهادی پس از بازبینی و تصویب قرارداد:

1. تصویب semantics تجاری و event schema توسط مالک Web2022 و Accounting.
2. تکمیل ADR امنیت، transport و API response.
3. تثبیت Registry mapping و قواعد ownership پس از inventory سازگاری.
4. ساخت contract tests در دو مخزن، بدون اتصال به پرداخت یا provisioning واقعی.
5. پیاده‌سازی مرحله‌ای Outbox/Inbox و observability در محیط آزمایشی.
6. آزمون retry، ordering، reconciliation و tenant idempotency.
7. طراحی و آزمون adapter ساخت Tenant و سپس ورود Dolibarr، هرکدام با تأیید مستقل.

هیچ مرحله بعدی صرفاً با merge این سند آغاز نمی‌شود؛ پیاده‌سازی runtime و تغییر production نیازمند درخواست و تأیید جداگانه‌اند.
