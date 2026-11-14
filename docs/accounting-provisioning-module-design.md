# طراحی داخلی ماژول Provisioning در پروژه Accounting

## ۱. هدف و وضعیت این سند

این سند گام بعدی معماری پس از تصمیم «Provisioning همان Accounting باشد» است. هدف، تبدیل تصمیم سطح محصول به مرزبندی ماژول‌ها، جریان‌های داخلی و قرارداد پیشنهادی API بین Web2022 و پروژه Accounting است.

- **تصمیم تثبیت‌شده:** Provisioning یک ماژول درون پروژه و مخزن فعلی Accounting است؛ سرویس یا مخزن مستقل دیگری ساخته نمی‌شود.
- **پیشنهاد معماری این سند:** یک Modular Monolith در برنامه Laravel موجود؛ مرزهای داخلی روشن، بدون جداکردن سرویس‌ها به deploymentهای مستقل.
- **هنوز تصویب‌نشده:** جزئیات نهایی schema دیتابیس، مسیرهای API، احراز هویت سرویس‌به‌سرویس، روش امضای رویداد، transport، worker/queue، driver نصب Dolibarr و زیرساخت اجرا.
- **محدوده:** طراحی مستنداتی؛ هیچ route، controller، model، migration، job، API یا رفتار runtime در این مرحله پیاده‌سازی نمی‌شود.

## ۲. شواهد وضع موجود و محدودیت‌های سازگاری

بررسی فایل‌های مخزن Accounting در شاخه کاری نشان می‌دهد:

- برنامه فعلی Laravel 12 و PHP با constraint برابر `^8.2` است.
- `routes/web.php` مسیرهای اصلی برنامه قدیمی، احراز هویت محلی و SSO را در خود دارد؛ گروه عمده مسیرهای قدیمی پشت middleware احراز هویت قرار گرفته‌اند.
- `AccountingSsoController` توکن Web2022 را مبادله می‌کند، کاربر/شرکت/نقش و entitlement قدیمی Laravel را می‌سازد یا به‌روزرسانی می‌کند و session همان برنامه را برقرار می‌سازد. این SSO، مسیر ورود به Dolibarr نیست.
- `AuthController` ورود محلی و session برنامه قدیمی را مدیریت می‌کند.
- `config/services.php` فعلاً تنظیمات SSO مربوط به Web2022 را دارد؛ این تنظیمات به‌تنهایی قرارداد امن API Provisioning نیستند.
- در بررسی مسیرهای شناخته‌شده، `routes/api.php` وجود نداشت؛ API داخلی Provisioning باید جداگانه و با middlewareهای مستقل طراحی شود.
- `composer.json` وابستگی Laravel 12 و PHPUnit 11 را ثبت می‌کند؛ `phpunit.xml` تست‌ها را با SQLite حافظه‌ای پیکربندی می‌کند.
- فایل‌های مدل و migration قدیمی، هویت و ساختار حسابداری Laravel را بازنمایی می‌کنند؛ نباید بدون inventory و تصمیم مهاجرت، آن‌ها را معادل Customer/Business/Tenant canonical در معماری جدید فرض کرد.

این بررسی، audit کامل همه مسیرها، migrationها، jobها و تست‌های موجود نیست. فهرست کامل فایل‌های تست در این مرحله از طریق دسترسی GitHub قابل استخراج نبود؛ پیش از هر تغییر runtime باید inventory محلی کامل و تست‌های مرتبط واقعاً اجرا شوند.

## ۳. شکل معماری پیشنهادی: Modular Monolith

پروژه و برنامه موجود Accounting حفظ می‌شود. Provisioning یک قابلیت/ماژول درون همان Accounting است و قرار نیست جایگزین کل پروژه یا باعث حذف قابلیت‌های فعلی آن شود. ماژول Provisioning از نظر مسئولیت، مرز داده و دسترسی با سایر قابلیت‌های Accounting تفکیک می‌شود تا بتواند در کنار آن‌ها کار کند.

لایه‌های پیشنهادی:

1. **HTTP/API layer:** دریافت درخواست Web2022، احراز هویت سرویس، اعتبارسنجی ورودی و تبدیل پاسخ.
2. **Application layer:** use caseهای مستقل مانند ایجاد کسب‌وکار، دریافت رویداد اشتراک، شروع provisioning، retry و reconciliation.
3. **Domain layer:** قواعد Customer، Business، Subscription projection، Tenant lifecycle، idempotency و ownership.
4. **Infrastructure layer:** Repositoryهای Registry، Inbox، queue/worker، audit، مدیریت secret و adapterهای cPanel/DB/filesystem/Dolibarr.
5. **Operations layer:** مشاهده وضعیت jobها، retry کنترل‌شده، گزارش خطا، health check و reconciliation؛ فقط برای اپراتور مجاز.

هیچ‌یک از این لایه‌ها نباید درخواست روزمره کاربر را از Dolibarr عبور دهند. پس از آماده‌شدن tenant، مرورگر مستقیماً با دامنه Dolibarr همان کسب‌وکار کار می‌کند.

## ۴. مرزبندی ماژول‌های داخلی

| ماژول | مسئولیت | خارج از مسئولیت |
|---|---|---|
| Customer Registry | شناسه canonical مشتری و نگاشت شناسه‌های خارجی Web2022 | احراز هویت روزمره مشتری در Dolibarr |
| Business Registry | ایجاد/ویرایش metadata مجاز، مالکیت و ارتباط Business با Customer | اطلاعات حسابداری و کاربران عملیاتی |
| External References | نگاشت namespace + external ID به شناسه canonical | استفاده از email یا ID Laravel به‌عنوان کلید اصلی |
| Subscription Projection | snapshot آخرین وضعیت تجاری اعلام‌شده از Web2022 و نسخه aggregate | دریافت وجه، کیف پول یا محاسبه مبلغ سفارش |
| Event Inbox | ثبت یکتای event، اعتبارسنجی schema/version و جلوگیری از پردازش تکراری | انتشار Outbox خرید؛ این مسئولیت در Web2022 است |
| Provisioning Orchestrator | ساخت/بازیابی tenant و هماهنگی jobهای مرحله‌ای | اجرای عملیات حسابداری داخل Dolibarr |
| Dolibarr Tenant Adapter | adapter محدود برای ایجاد DB، فایل‌ها، config، release و بررسی سلامت | مدیریت رمز و نقش کاربران نهایی |
| Tenant Lifecycle | وضعیت عملیاتی tenant و گذارهای مجاز | مالکیت وضعیت تجاری پرداخت |
| Audit & Reconciliation | ردگیری رویدادها، تغییرات و اختلاف دو سامانه | ثبت secret یا داده حساس پرداخت در log |

### مرز ماژول Provisioning با قابلیت‌های موجود Accounting

- ماژول جدید نباید از مدل‌های `User`, `Account`, `Company`, `Personnel` یا `SubscriptionEntitlement` به‌عنوان مدل canonical مشتری، کسب‌وکار یا tenant استفاده کند.
- ماژول جدید نباید از session کاربر Laravel یا SSO قدیمی برای احراز هویت درخواست‌های Backend وب‌سایت استفاده کند.
- استفاده مجدد از دیتابیس فعلی برای Registry، یا ساخت دیتابیس جدا برای Registry، هنوز تصمیم زیرساختی نهایی نیست؛ اما جدول‌ها و داده‌های قدیمی نباید بی‌برنامه تغییر یا حذف شوند.
- تنظیمات SSO فعلی و secret آن نباید به‌عنوان credential API جدید بازاستفاده شوند.
- قابلیت‌ها و داده‌های فعلی Accounting باید حفظ شوند و Provisioning نباید آن‌ها را ناخواسته مختل کند. این سند هیچ قابلیت موجودی را legacy یا قابل بازنشستگی اعلام نمی‌کند و مجوز حذف، خاموش‌کردن یا مهاجرت آن‌ها نیست.

## ۵. مدل داده منطقی

این بخش مدل مفهومی است و **schema نهایی یا migration نیست**.

| موجودیت | فیلدهای مفهومی | قواعد |
|---|---|---|
| Customer | UUID، وضعیت، زمان ایجاد | canonical؛ مستقل از شناسه Web2022 |
| Business | UUID، customer UUID، نام نمایشی، وضعیت، metadata مجاز | هر Business دقیقاً یک Customer دارد |
| ExternalReference | namespace، external ID، نوع موجودیت، canonical ID | قید یکتا روی namespace + external ID |
| SubscriptionProjection | Web2022 subscription ID، business UUID، وضعیت، plan code، period، aggregate version | snapshot است؛ منبع حقیقت تجاری Web2022 می‌ماند |
| Tenant | UUID، business UUID، domain، release، resource references، lifecycle status | در مدل پایه حداکثر یک tenant فعال برای هر Business |
| EventInbox | event ID، schema version، aggregate ID/version، payload hash، receive/process status | event ID یکتا و immutable |
| ProvisioningJob | job UUID، tenant UUID، operation، status، attempts، next retry، error code | هر مرحله قابل retry و قابل audit |
| AuditLog | actor/service، action، resource IDs، timestamp، correlation ID، result | بدون secret و داده حساس |
| TenantResource | tenant UUID، resource type، opaque reference، lifecycle | password و secret خام در Registry ذخیره نشود |

### قیود کلیدی

- UUIDهای canonical توسط پروژه Accounting و قابلیت Provisioning آن تولید می‌شوند.
- شناسه اشتراک را Web2022 تولید و مالکیت می‌کند.
- email، شماره موبایل، نام کسب‌وکار و شناسه داخلی Laravel، شناسه canonical نیستند.
- Business A و Business B حتی اگر متعلق به یک Customer باشند، Tenant، DB، config، documents و session مستقل دارند.
- تمدید اشتراک نباید Tenant یا DB جدید بسازد.
- حذف فیزیکی Business/Tenant در MVP تعریف نشده؛ فقط lifecycle و بایگانی با سیاست نگهداری مشخص قابل بررسی است.

## ۶. قرارداد پیشنهادی API داخلی

تمام مسیرهای زیر **پیشنهادی** هستند تا قرارداد قابل بررسی باشد؛ هنوز endpoint نهایی یا پیاده‌سازی‌شده محسوب نمی‌شوند.

Base path پیشنهادی: `/api/internal/v1`

| Method | Path پیشنهادی | کاربرد |
|---|---|---|
| POST | `/customers` | ایجاد یا resolve کردن Customer بر اساس external reference احرازشده |
| GET | `/customers/{customer_id}` | خواندن Customer مجاز |
| POST | `/customers/{customer_id}/businesses` | ایجاد Business |
| GET | `/customers/{customer_id}/businesses` | فهرست Businessهای متعلق به Customer |
| GET | `/businesses/{business_id}` | خواندن Business |
| PATCH | `/businesses/{business_id}` | تغییر metadata مجاز Business |
| POST | `/events` | دریافت envelope رویدادهای تجاری نسخه‌بندی‌شده |
| GET | `/businesses/{business_id}/provisioning-status` | خواندن وضعیت آماده‌سازی و tenant |
| POST | `/operations/{operation_id}/retry` | retry کنترل‌شده؛ فقط برای principal اپراتوری مجاز، نه مرورگر مشتری |

### قواعد API

- API داخلی فقط از Backend احرازاصالت‌شده Web2022 یا principal اپراتوری مجاز قابل فراخوانی است؛ مرورگر مشتری مستقیم به آن دسترسی ندارد.
- هر درخواست باید هویت caller، scope و ownership Customer/Business را سمت سرور اعتبارسنجی کند. پذیرفتن `customer_id` ارسالی بدون بررسی مجاز نیست.
- پاسخ‌ها باید شناسه canonical، وضعیت و correlation ID را برگردانند؛ هیچ DB credential، filesystem path خصوصی یا secret نباید به Web2022 افشا شود.
- ایجاد منابع باید idempotent باشد؛ برای درخواست‌های تغییردهنده از کلید idempotency یا external reference یکتا استفاده شود.
- API باید versioned باشد؛ تغییر ناسازگار به نسخه جدید نیاز دارد.
- قرارداد HTTP status، pagination، rate limit، request signing و auth scheme در ADR امنیتی نهایی می‌شود.
- عملیات مدیریتی tenant (مانند retry دستی، restore، suspend) از API عمومی Web2022 جدا می‌ماند و نیازمند مجوز اپراتوری و audit است.

## ۷. قرارداد رویداد اشتراک

Web2022 منبع حقیقت خرید، پرداخت و وضعیت تجاری اشتراک است. پس از commit موفق تراکنش تجاری، Web2022 باید Outbox خود را ثبت کند؛ Accounting فقط Inbox و پردازش رویداد را مالک است.

رویدادهای موردنیاز برای طراحی:
- `subscription.activated`
- `subscription.renewed`
- `subscription.expired`
- `subscription.suspended`
- `subscription.resumed`
- `subscription.cancelled`
- `payment.refunded`
- `payment.chargeback`

### envelope نمونه (قرارداد مفهومی)

```json
{
  "event_id": "uuid",
  "event_type": "subscription.activated",
  "schema_version": 1,
  "occurred_at": "2026-11-14T10:00:00Z",
  "aggregate": {
    "type": "subscription",
    "id": "web2022-subscription-reference",
    "version": 1
  },
  "customer_id": "accounting-customer-uuid",
  "business_id": "accounting-business-uuid",
  "subscription_id": "web2022-subscription-reference",
  "plan_code": "plan-code",
  "period": {
    "starts_at": "2026-11-14T10:00:00Z",
    "ends_at": "2027-11-14T10:00:00Z"
  },
  "references": {
    "order_id": "opaque-order-reference",
    "payment_id": "opaque-payment-reference"
  }
}
```

نمونه بالا داده واقعی نیست و فقط ساختار مفهومی را نشان می‌دهد. اطلاعات کارت، رمز، token درگاه، secret یا داده غیرضروری شخصی نباید در payload باشد.

### پردازش رویداد

1. اعتبارسنجی هویت سرویس، امضا/تازگی پیام و schema.
2. ثبت event در Inbox با unique event ID و payload hash.
3. اگر event ID قبلاً با payload یکسان پردازش شده، نتیجه قبلی بازگردانده شود و اثر جدیدی ایجاد نشود.
4. اگر event ID تکراری با payload متفاوت بود، درخواست رد و رخداد امنیتی ثبت شود.
5. aggregate version قدیمی‌تر نباید snapshot جدید را بازنویسی کند؛ event خارج از ترتیب باید طبق سیاست نهایی نگهداری، رد یا reconciliation شود.
6. ثبت/به‌روزرسانی SubscriptionProjection و ایجاد job لازم در یک مرز تراکنشی سازگار.
7. اجرای jobها مستقل از درخواست HTTP؛ پاسخ API صرفاً دریافت رویداد و شناسه پیگیری را تأیید کند، نه آماده‌شدن فوری tenant را.
8. خطاهای موقت retry شوند؛ خطاهای دائمی یا exhausted برای بررسی اپراتور ثبت شوند.
9. نتیجه نهایی و lifecycle tenant از endpoint وضعیت قابل خواندن باشد.

## ۸. چرخه عمر tenant و orchestration

وضعیت عملیاتی tenant از وضعیت تجاری اشتراک جداست.

وضعیت‌های مفهومی:
- `not_created`
- `provisioning`
- `ready`
- `provisioning_failed`
- `maintenance`
- `restore_in_progress`
- `suspended`

گذارهای اصلی:

```text
not_created -> provisioning -> ready
                    |
                    +-> provisioning_failed -> provisioning (retry)
ready -> maintenance -> ready
ready -> restore_in_progress -> ready
ready/provisioning_failed -> suspended (authorized administrative action)
```

### مراحل provisioning اولیه

1. رزرو tenant UUID و قفل idempotency برای Business.
2. اعتبارسنجی اینکه Business و اشتراک مجازند و tenant قبلی وجود ندارد.
3. تخصیص domain و release مجاز.
4. ساخت DB و DB user مستقل tenant.
5. آماده‌سازی درخت اجرایی Dolibarr و config خصوصی tenant.
6. آماده‌سازی مسیر اسناد و دسترسی‌های filesystem.
7. اجرای installer/initialization از طریق adapter امن و نسخه‌بندی‌شده.
8. قفل‌کردن installer و بررسی عدم دسترسی عمومی به config و documents خصوصی.
9. health check، بررسی اتصال DB و آزمون جداسازی منابع.
10. ثبت resource references، audit و تغییر وضعیت به `ready`.
11. انتشار نتیجه آماده‌سازی برای Web2022 تا مشتری به Dolibarr همان کسب‌وکار هدایت شود.

این مراحل توصیف هدف‌اند؛ روش اجرای واقعی installer، ساخت DB، فایل‌ها، subdomain و deployment هنوز باید با محدودیت‌های cPanel و runtime آزموده شود.

### تمدید

- تمدید معتبر همان subscription و tenant/DB را ادامه می‌دهد.
- هیچ DB، config یا tenant جدیدی صرفاً به‌خاطر تمدید ساخته نمی‌شود.
- retry رویداد تمدید فقط snapshot دوره را idempotent به‌روزرسانی می‌کند.
- ایجاد سال مالی جدید موضوع مستقل حسابداری مشتری است و از تمدید اشتراک نتیجه نمی‌شود.

### انقضا و تعلیق

- انقضای عادی entitlement با lifecycle tenant یکی نیست؛ tenant و داده‌ها حفظ می‌شوند و سیاست محصولی read-only قبلاً تأییدشده باید در طراحی runtime جداگانه اعمال شود.
- تعلیق امنیتی/اداری، وضعیت نامشخص و اشتراک شروع‌نشده از انقضای عادی متمایزند.
- این سند هیچ enforcement یا تغییر runtime برای read-only، session، login یا APIهای Dolibarr مجاز نمی‌کند.

## ۹. امنیت و اعتماد بین سامانه‌ها

موارد الزامی برای طراحی امنیتی پیش از پیاده‌سازی:

- TLS برای تمام ارتباطات.
- هویت سرویس مستقل برای Web2022 و principal اپراتوری؛ حداقل scope و امکان rotation.
- تصمیم نهایی بین HMAC امضای درخواست و امضای نامتقارن در ADR امنیتی جداگانه.
- timestamp، nonce/event ID و replay protection.
- محدودیت اندازه payload، schema validation، rate limiting و ثبت audit.
- نگهداری secretها خارج Git و log؛ رمز DB هر tenant در مسیر امن میزبان/secret store.
- جلوگیری از افشای شناسه tenant دیگر با authorization و ownership check در هر endpoint.
- tenant context فقط از Registry معتبر استخراج شود؛ از host/header دلخواه کاربر به‌تنهایی نتیجه‌گیری نشود.
- Web2022 به credentialهای Dolibarr یا DB tenantها دسترسی نگیرد.
- Provisioning رمز کاربران روزمره Dolibarr را ایجاد، بازیابی یا نگهداری نکند.
- هیچ token یا secret در URL مرورگر قرار نگیرد.

## ۱۰. Retry، صف و reconciliation

- درخواست HTTP ایجاد Business یا دریافت Event نباید منتظر تکمیل نصب Dolibarr بماند.
- Inbox event و provisioning job باید مرز تراکنشی قابل بازیابی داشته باشند.
- هر مرحله job باید ورودی، خروجی، وضعیت، شمارنده تلاش، زمان retry و error code ساخت‌یافته داشته باشد.
- retry فقط برای خطاهای transient و با backoff؛ خطای دائمی نباید بی‌نهایت تکرار شود.
- قفل هم‌زمانی برای جلوگیری از ساخت دو tenant برای یک Business الزامی است.
- jobهای تکراری باید منابع قبلی را تشخیص دهند و ادامه دهند؛ نه اینکه DB یا پوشه جدید بسازند.
- DLQ یا وضعیت نیازمند مداخله برای خطاهای exhausted لازم است.
- reconciliation باید Web2022 subscription snapshot، Inbox، job و tenant registry را تطبیق دهد؛ هرگز پرداخت را دوباره اجرا نکند.
- در محیطی که فقط Cron تأیید شده، استفاده از polling دوره‌ای فقط پس از آزمون محدودیت زمان اجرا، قفل و هم‌زمانی مجاز خواهد بود؛ worker دائمی یا queue broker را موجود فرض نمی‌کنیم.

## ۱۱. مشاهده‌پذیری و عملیات

حداقل فیلدهای قابل جست‌وجو:
- correlation ID، event ID، aggregate ID/version؛
- customer/business/subscription/tenant UUID؛
- provisioning job ID و operation؛
- lifecycle status، attempt count و زمان‌های شروع/پایان؛
- error code غیرحساس و نتیجه آخرین health check.

پنل اپراتوری احتمالی فقط پس از طراحی authorization و audit ساخته می‌شود. retry، suspend، restore و تغییر domain عملیات privileged هستند و باید دلیل، عامل، زمان و نتیجه آن‌ها ثبت شود.

## ۱۲. برنامه پیاده‌سازی مرحله‌ای و دروازه‌های تأیید

| مرحله | خروجی | پیش‌شرط |
|---|---|---|
| ۰. Inventory و سازگاری با Accounting موجود | فهرست route/model/migration/job/session/data و وابستگی‌ها؛ تعیین نقاط همزیستی و مرزهای ماژول | بدون حذف یا migration مخرب |
| ۱. تصمیم Registry | تصویب DB مقصد، schema، UUID و external references | backup/restore و سازگاری با داده موجود |
| ۲. API امن | احراز هویت سرویس، Customer/Business endpoints و authorization | تصویب ADR امنیتی و قرارداد Web2022 |
| ۳. Event ingestion | schema نسخه‌بندی‌شده، Inbox، idempotency، ordering و reconciliation | هماهنگی تغییرات Web2022 و تست قرارداد دوطرفه |
| ۴. Provisioning adapter | ایجاد tenant در محیط آزمایشی، health checks و retry | اثبات واقعی cPanel/runtime و tenant isolation |
| ۵. Lifecycle و عملیات | renewal، expiration snapshot، suspend، restore، audit و operator controls | runbook و آزمون recovery |
| ۶. Cutover محدود | هدایت مشتری آزمایشی از خرید به Dolibarr | backup/restore، امنیت، پذیرش و مجوز صریح production |

هیچ مرحله بعدی صرفاً با موفقیت مستندات آغاز نمی‌شود؛ دروازه‌های فنی و تأییدهای لازم باید جداگانه تکمیل شوند.

## ۱۳. ماتریس آزمون موردنیاز پیش از runtime

- Customer/Business ownership: عدم دسترسی یک مشتری به Business مشتری دیگر.
- External reference: عدم ایجاد رکورد canonical تکراری برای یک namespace/reference.
- Idempotency: event و job تکراری فقط یک اثر دارند.
- Ordering: رویداد قدیمی entitlement جدید را بازنویسی نمی‌کند.
- Provisioning retry: شکست در هر مرحله و اجرای مجدد، منابع تکراری نمی‌سازد.
- Renewal: tenant و DB قبلی حفظ می‌شوند.
- Multi-tenant isolation: DB، DB user، config، documents، session، cache، runtime و domain بین دو tenant جدا هستند.
- Secret handling: هیچ secret در پاسخ API، URL، log یا Git دیده نمی‌شود.
- Authorization: فقط Web2022 service و operator مجاز می‌توانند عملیات مربوط را انجام دهند.
- Recovery: backup/restore Registry و tenant به‌صورت مستقل آزموده می‌شود.
- Contract tests: Web2022 producer و Accounting consumer روی event schema و نسخه‌ها سازگارند.

این موارد برنامه آزمون هستند؛ در این تغییر اجرا نشده‌اند.

## ۱۴. تصمیم‌های باز که باید پیش از کدنویسی تعیین شوند

1. Registry از دیتابیس فعلی Laravel استفاده کند یا دیتابیس مستقل کنترل‌پلین داشته باشد؟ تصمیم باید پس از inventory داده‌ها و backup/restore باشد.
2. احراز هویت Web2022 به Accounting با HMAC، کلید نامتقارن یا روش دیگری انجام شود؟
3. رویدادها با HTTP push ارسال شوند یا transport دیگری؟ Outbox Web2022 چگونه retry و تحویل را تضمین کند؟
4. worker/queue در cPanel با Cron اجرا شود یا کنترل‌پلین روی runtime/سرور جدا قرار گیرد؟
5. روش امن ساخت DB، DB user، filesystem و اجرای Dolibarr installer دقیقاً چیست؟
6. domain و TLS هر Business چگونه تخصیص و تمدید شوند؟
7. Customer و مالکیت چندکاربره/انتقال Business در Web2022 چگونه احراز و به Registry نگاشت شوند؟
8. semantics دقیق refund، chargeback، cancel و suspend چیست و هرکدام چه اثری بر tenant دارند؟
9. روش ورود مستقیم یا SSO به Dolibarr چیست؟ هیچ تصمیم یا پیاده‌سازی در این سند اتخاذ نشده است.
10. قابلیت‌ها و داده‌های فعلی Accounting چگونه در کنار Provisioning و Dolibarr ادامه پیدا کنند؟ هر انتقال یا حذف مشخص فقط با تصمیم و تأیید مستقل انجام می‌شود.

تا زمان تصمیم جداگانه، هیچ runtime، migration، DB، payment، SSO، read-only enforcement، cPanel، DNS، production یا حذف قابلیت‌های موجود Accounting/Laravel انجام نمی‌شود.
