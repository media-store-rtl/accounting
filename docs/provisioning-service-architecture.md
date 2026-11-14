# معماری قابلیت Provisioning در پروژه Accounting و Registry کسب‌وکارها

> جزئیات طراحی داخلی ماژول‌ها، API پیشنهادی، event contract، lifecycle و مراحل پیاده‌سازی در [طراحی داخلی ماژول Provisioning در Accounting](accounting-provisioning-module-design.md) آمده است.

## وضعیت تصمیم

این سند معماری مورد تأیید کاربر را ثبت می‌کند؛ قرارداد API، schema نهایی رویداد، انتخاب فناوری، محل استقرار و پیاده‌سازی runtime هنوز تصویب نشده‌اند.

تصمیم‌های تثبیت‌شده:
- Web2022 رابط مشتری برای ایجاد و مدیریت کسب‌وکارها و مالک checkout، پرداخت، سفارش و وضعیت تجاری اشتراک است.
- Registry canonical مشتری/کسب‌وکار/tenant و چرخه عمر محیط‌ها متعلق به ماژول Provisioning درون پروژه Accounting است.
- Dolibarr مقصد مستقیم کاربران و مالک عملیات حسابداری/ERP، هویت عملیاتی، کاربران، نقش‌ها و مجوزهای هر کسب‌وکار است.
- یک مشتری می‌تواند چند کسب‌وکار داشته باشد؛ هر کسب‌وکار اشتراک مستقل و یک tenant مستقل Dolibarr دارد.
- پروژه و قابلیت‌های فعلی Accounting حفظ می‌شوند؛ Provisioning قابلیت جدیدی درون همان پروژه است. مرزهای ماژولی و امنیتی باید از تداخل و وابستگی ناخواسته جلوگیری کنند، نه اینکه قابلیت‌های موجود را حذف یا جایگزین کنند. مدل‌ها، sessionها، دیتابیس و داده‌های فعلی تا زمان inventory و تصمیم مستقل باید حفظ شوند.
- هیچ کد runtime، DB، سرور، cPanel، DNS، پرداخت، SSO، migration یا production با این سند تغییر نمی‌کند.

## ۱. مرز مسئولیت‌ها

| مسئولیت | Web2022 | Provisioning | Dolibarr |
|---|---|---|---|
| رابط مشتری برای ایجاد/مدیریت کسب‌وکار | مالک UI | API و Registry canonical | — |
| شناسه پایدار مشتری و کسب‌وکار | نگهداری مرجع | مالک و تولیدکننده | مصرف‌کننده در صورت نیاز |
| پلن، checkout، کیف پول/درگاه و سفارش | مالک | دریافت رویداد | — |
| وضعیت تجاری اشتراک و پرداخت | منبع حقیقت | snapshot لازم برای عملیات | مصرف‌کننده entitlement |
| ساخت و نگهداری tenant | — | مالک | محیط مقصد |
| DB، config، فایل‌ها و runtime مستقل | — | orchestration و registry | داده عملیاتی tenant |
| ورود و حساب‌های کاربران عملیاتی | — | بدون مالکیت رمز/حساب | مالک |
| نقش‌ها و مجوزهای ERP | — | — | مالک |
| اسناد و عملیات حسابداری | — | — | مالک |

Web2022 در این تصمیم، رابط کسب‌وکار و سامانه تجاری است؛ اما شناسه canonical کسب‌وکار را خودش تولید نمی‌کند. تمام درخواست‌های مدیریتی به Registry از Backend احرازاصالت‌شده Web2022 انجام می‌شود، نه مستقیماً از مرورگر.

این تصمیم به معنی تضمین حفظ دائمی فناوری یا مخزن Web2022 نیست. جایگزینی احتمالی آن در آینده باید با نگهداری شناسه‌ها و داده‌های canonical در Provisioning امکان‌پذیر باشد.

## ۲. مدل مفهومی و cardinality

```text
Customer (Provisioning customer_id)
  |
  +-- Business A (Provisioning business_id)
  |     +-- Subscription A (Web2022 subscription_id)
  |     +-- Tenant A (Provisioning tenant_id)
  |           +-- Dolibarr DB A
  |           +-- Config / Documents / Session A
  |
  +-- Business B (Provisioning business_id)
        +-- Subscription B (Web2022 subscription_id)
        +-- Tenant B (Provisioning tenant_id)
              +-- Dolibarr DB B
              +-- Config / Documents / Session B
```

قواعد مدل:
1. یک Customer می‌تواند صفر یا چند Business داشته باشد.
2. هر Business دقیقاً به یک Customer تعلق دارد.
3. هر Business در مدل پایه حداکثر یک Tenant عملیاتی دارد؛ tenant با تغییر پلن یا تمدید جایگزین نمی‌شود.
4. هر Business اشتراک و دوره اعتبار مستقل دارد. در MVP فرض طراحی، یک اشتراک جاری برای هر Business در نظر گرفته می‌شود؛ اشتراک‌های هم‌زمان یا چند محصولی هنوز تصمیم‌گیری نشده‌اند.
5. هر Tenant یک DB مستقل Dolibarr و مسیرهای config، documents، session و runtime مستقل دارد.
6. ایجاد Business می‌تواند پیش از خرید انجام شود؛ ایجاد Tenant پس از رویداد فعال‌سازی معتبر اشتراک انجام می‌شود.
7. شناسه‌های Customer، Business، Subscription و Tenant مجزا هستند و نباید از روی یکدیگر مشتق شوند.

### شناسه‌ها و مرجع خارجی

| شناسه | تولیدکننده و مالک | نگهداری در سامانه دیگر |
|---|---|---|
| `customer_id` | Provisioning | Web2022 به‌عنوان مرجع |
| `business_id` | Provisioning | Web2022 برای checkout و مدیریت |
| `subscription_id` | Web2022 | Provisioning به‌عنوان مرجع خارجی |
| `tenant_id` | Provisioning | Web2022 برای نمایش وضعیت/لینک |

شناسه داخلی حساب Web2022 می‌تواند به‌عنوان `external_customer_ref` ذخیره شود، اما شناسه canonical نیست. ایمیل، شماره تلفن، نام کسب‌وکار و شناسه Laravel کلید یکتا یا جایگزین UUID نیستند. نگاشت شناسه‌های خارجی باید namespace داشته باشد تا تعویض سامانه فروش باعث برخورد شناسه نشود.

## ۳. جریان ایجاد و مدیریت کسب‌وکار

1. مشتری از رابط Web2022 درخواست ایجاد کسب‌وکار می‌دهد.
2. Backend Web2022 با هویت سرویس و مجوز محدود، درخواست را به Provisioning می‌فرستد.
3. Provisioning مشتری را با external reference پیدا یا ایجاد می‌کند و یک `business_id` پایدار می‌سازد.
4. Provisioning فقط حداقل metadata لازم برای Registry و عملیات را نگه می‌دارد؛ داده‌های حقوقی/حسابداری و کاربران عملیاتی در Dolibarr مدیریت می‌شوند، مگر قرارداد داده‌ای جداگانه‌ای تصویب شود.
5. Web2022 شناسه canonical را ذخیره می‌کند و برای نمایش و مدیریت بعدی از آن استفاده می‌کند.
6. ویرایش metadata، بایگانی و تغییر مالکیت باید از API Provisioning عبور کند تا Registry و کنترل دسترسی همگام بمانند.

API دقیق هنوز تعیین نشده است. عملیات منطقی موردنیاز شامل ایجاد/بازیابی مشتری، ایجاد کسب‌وکار، خواندن فهرست کسب‌وکارهای مجاز مشتری، خواندن جزئیات و تغییر metadata مجاز است. عملیات حذف فیزیکی کسب‌وکار در MVP مجاز/تعریف‌شده نیست؛ بایگانی و سیاست نگهداری باید جداگانه تصویب شود.

## ۴. جریان خرید، فعال‌سازی و تمدید

1. مشتری در Web2022 یک Business مشخص را انتخاب می‌کند.
2. Web2022 خرید و پرداخت را انجام می‌دهد؛ وضعیت پرداخت و اشتراک فقط پس از ثبت قطعی و قابل ممیزی در سامانه تجاری معتبر است.
3. در همان تراکنش تجاری، Web2022 یک پیام Outbox ثبت می‌کند؛ انتشار پیام پس از commit انجام می‌شود.
4. پیام فعال‌سازی شامل `business_id`، `subscription_id`، شناسه رویداد، نسخه schema، زمان وقوع، نسخه aggregate، plan code و دوره اعتبار است. اطلاعات کارت، رمز و secret در پیام قرار نمی‌گیرد.
5. Provisioning امضا و تازگی پیام را اعتبارسنجی می‌کند، event را در Inbox با قید یکتا ثبت و پردازش را آغاز می‌کند.
6. در خرید اول، Provisioning tenant را ایجاد و منابع مستقل را آماده می‌کند. پس از آزمون سلامت و جداسازی، وضعیت عملیاتی به `ready` می‌رسد.
7. در تمدید، همان subscription و tenant/DB حفظ و فقط دوره entitlement به‌روزرسانی می‌شود؛ ساخت DB جدید ممنوع است.
8. Web2022 نتیجه و وضعیت آماده‌سازی را از API وضعیت یا سازوکار callback تأییدشده دریافت می‌کند؛ انتخاب callback در برابر polling هنوز باز است.
9. پس از آماده‌شدن، مشتری مستقیماً به دامنه Dolibarr همان Business هدایت می‌شود. Provisioning پراکسی درخواست‌های روزمره کاربران نیست.

پرداخت موفق و Provisioning دو فرایند مستقل‌اند: شکست یا retry ماژول Provisioning پروژه Accounting هرگز نباید خرید یا برداشت وجه را دوباره اجرا کند.

## ۵. قرارداد رویداد و تحویل قابل‌اعتماد

رویدادهای پیشنهادی:
- `subscription.activated`
- `subscription.renewed`
- `subscription.expired`
- `subscription.suspended`
- `subscription.resumed`
- `subscription.cancelled`
- `payment.refunded`
- `payment.chargeback`

حداقل envelope پیشنهادی:
- `event_id`: UUID یکتا؛
- `event_type` و `schema_version`؛
- `occurred_at`؛
- `aggregate.type`، `aggregate.id` و `aggregate.version`؛
- `customer_id`، `business_id` و `subscription_id`؛
- `plan_code` و `period_start/period_end` در رویدادهای مرتبط؛
- reference سفارش/پرداخت برای ممیزی و reconciliation، بدون اطلاعات حساس پرداخت.

قواعد تحویل:
1. **Outbox در Web2022:** رویداد فقط همراه commit خرید/تمدید قطعی ثبت شود؛ انتشارگر مجدداً تلاش کند تا تحویل موفق یا وضعیت قابل بررسی حاصل شود.
2. **Inbox در Provisioning:** `event_id` یکتا باشد؛ دریافت دوباره همان رویداد اثر تکراری نداشته باشد.
3. **Idempotency:** کلید idempotency برای عملیات ساخت tenant و jobهای وابسته ثبت شود؛ اجرای مجدد باید منابع قبلی را پیدا و ادامه دهد.
4. **ترتیب رویداد:** نسخه aggregate یا sequence به ازای اشتراک کنترل شود؛ رویداد قدیمی نباید وضعیت جدیدتر را بازنویسی کند.
5. **Retry و DLQ:** خطاهای موقت با backoff تلاش مجدد شوند؛ خطاهای دائمی یا exhausted به صف/وضعیت بررسی دستی منتقل شوند.
6. **Reconciliation:** گزارش دوره‌ای اختلاف وضعیت تجاری Web2022 با snapshot دریافت‌شده و وضعیت tenant در Provisioning تولید شود.
7. **Correlation:** شناسه‌های event، order، subscription، business و tenant در audit/log قابل ردیابی باشند؛ secret و داده حساس ثبت نشوند.

ساختار دقیق JSON، HTTP status، امضای HMAC یا asymmetric، key rotation، پنجره زمانی ضد replay و سیاست retry نهایی نیازمند ADR/API specification مستقل است. secret فعلی SSO نباید بدون طراحی و تصویب مجدد بازاستفاده شود.

## ۶. مالکیت وضعیت‌ها

### وضعیت تجاری — Web2022 منبع حقیقت

وضعیت‌های مفهومی شامل pending، active، expired، suspended، cancelled، refunded و chargeback هستند. تعریف دقیق گذارها و زمان اثر لغو/بازپرداخت هنوز باید تصویب شود.

### وضعیت عملیاتی — Provisioning منبع حقیقت

- `not_created`
- `provisioning`
- `ready`
- `provisioning_failed`
- `maintenance`
- `restore_in_progress`
- `suspended`

وضعیت اشتراک نباید با وضعیت tenant یکی شود. برای مثال پرداخت می‌تواند قطعی و اشتراک active باشد، درحالی‌که tenant در provisioning_failed قرار دارد. این وضعیت باید قابل retry و reconciliation باشد و پرداخت دوباره نمی‌خواهد.

### سیاست دسترسی

سیاست محصولی قبلاً تأییدشده باقی می‌ماند:
- انقضای عادی: tenant و داده‌ها حفظ شوند و محیط طبق سیاست محصول Read-Only باشد.
- تمدید: همان محیط قبلی دوباره فعال شود.
- تعلیق امنیتی/اداری، اشتراک شروع‌نشده یا وضعیت نامشخص/اعتبارسنجی ناموفق: دسترسی طبق سیاست fail-closed مسدود شود.
- انقضا یا تعلیق یک Business نباید Business دیگر همان Customer را تغییر دهد.

این سند enforcement در Dolibarr، invalidation نشست‌ها یا entitlement runtime را طراحی/پیاده‌سازی نمی‌کند؛ برای آن درخواست و طراحی مستقل لازم است.

## ۷. ساختار Registry و jobها

موجودیت‌های منطقی پیشنهادی (نه schema/migration نهایی):

| موجودیت | مسئولیت |
|---|---|
| `customers` | UUID canonical و external customer references |
| `businesses` | UUID canonical، customer_id و metadata حداقلی |
| `tenants` | business_id، tenant_id، domain، release، resource references و lifecycle status |
| `subscriptions` | external subscription_id، business_id، آخرین نسخه رویداد و snapshot entitlement |
| `event_inbox` | event_id، schema، aggregate version، زمان دریافت و نتیجه |
| `provisioning_jobs` | نوع عملیات، tenant_id، تلاش، زمان‌بندی و خطا |
| `audit_logs` | عامل، عملیات، شناسه‌های مرتبط، زمان و نتیجه |
| `external_references` | namespace، external ID و ارتباط با شناسه canonical |

Registry و دیتابیس Provisioning که در پروژه Accounting میزبانی می‌شود باید از دیتابیس‌های عملیاتی tenantهای Dolibarr مستقل باشد. secretهای DB/API در secret store یا سازوکار امن میزبان نگهداری شوند؛ رمزها در Git، log یا metadata عمومی قرار نگیرند.

## ۸. مرز عملیاتی Dolibarr

- هر Business به یک محیط Dolibarr اختصاصی و DB مستقل نگاشت می‌شود.
- config، مسیر اسناد، session/cookie context، cache/temp و runtime باید tenant-specific باشند.
- با توجه به وضعیت فعلی مستندات cPanel، روش محافظه‌کارانه کپی release نسخه‌بندی‌شده و بررسی‌شده در درخت اجرایی مستقل هر tenant است؛ shared physical htdocs یا انتخاب پویای DB تا اثبات runtime و آزمون امنیتی تأیید نشده است.
- ارتقای release کد، به‌تنهایی به معنی ارتقای موفق DB همه tenantها نیست؛ هر DB باید backup، upgrade و verification مستقل داشته باشد.
- Provisioning حساب روزمره مدیر Dolibarr یا رمز کاربران را مالک نمی‌شود. اگر bootstrap اولیه لازم باشد، باید یک فرایند محدود، auditشده و امن با حداقل دسترسی طراحی شود.
- پس از آماده‌سازی، مرورگر مشتری مستقیم با Dolibarr همان tenant کار می‌کند.

## ۹. امنیت، جداسازی و مشاهده‌پذیری

حداقل الزامات پیش از production:
- TLS و احراز هویت سرویس به سرویس با حداقل مجوز؛
- امضای پیام، timestamp، event ID، جلوگیری از replay و چرخش کلید؛
- عدم قرار دادن token/secret در URL و عدم استفاده مجدد از secret قدیمی SSO؛
- محدودسازی دسترسی Web2022 به Businessهای همان Customer؛
- کنترل ownership و authorization در Provisioning، نه اعتماد به customer_id ارسالی بدون اعتبارسنجی؛
- جداسازی DB، DB user، config، files، documents، sessions، cache و runtime برای هر tenant؛
- جلوگیری از دسترسی tenant به مسیرها و داده tenant دیگر؛
- audit trail، correlation ID، health checks، alerting و runbook برای retry/restore؛
- backup و آزمون restore مستقل Registry و هر tenant؛
- تست‌های دو tenant حداقل برای DB، filesystem، URL، session/cookie و permission isolation.

## ۱۰. وابستگی زیرساختی و cPanel

ماژول Provisioning پروژه Accounting از نظر معماری مستقل است، اما محل اجرا هنوز تعیین نشده است. گزینه‌ها می‌توانند runtime جدا روی همان hosting یا سرور مستقل باشند؛ تصمیم باید بر اساس نسخه PHP/DB، منابع، محدودیت process، worker/queue، cron، دسترسی شبکه، secret management و قابلیت backup گرفته شود.

وجود Cron در cPanel طبق اعلام کاربر در دسترس است؛ این موضوع به‌تنهایی پشتیبانی worker دائمی، queue broker، process manager یا ظرفیت اجرای امن provisioning را اثبات نمی‌کند. اگر MVP از polling دوره‌ای Cron استفاده کند، باید ابتدا محدودیت زمان اجرا، قفل هم‌زمانی، retry و عدم اجرای job تکراری روی محیط واقعی آزموده شود.

## ۱۱. سناریوهای خطا و بازیابی

| سناریو | رفتار الزامی |
|---|---|
| رویداد فعال‌سازی تکراری | یک tenant و یک مجموعه منابع؛ بدون اثر تکراری |
| DB ساخته شده، پاسخ API گم شده | retry tenant موجود را پیدا کند و ادامه دهد |
| provisioning شکست خورده پس از پرداخت | اشتراک/پرداخت تکرار نشود؛ job قابل retry و reconciliation باشد |
| تمدید قبل از پایان provisioning اولیه | دوره جدید ثبت شود؛ ساخت موازی tenant ایجاد نشود |
| رویداد منقضی قدیمی بعد از تمدید برسد | aggregate version آن را رد یا quarantine کند |
| tenant A تعلیق شود | tenant B همان مشتری بدون تغییر بماند |
| restore یک tenant | فقط DB/files/config همان tenant بازیابی شود |
| Web2022 موقتاً در دسترس نباشد | وضعیت آخرین entitlement با سیاست اعتبار/انقضای مشخص و fail-closed مدیریت شود؛ مدت cache هنوز تصمیم باز است |

## ۱۲. تصمیم‌های باز برای ADR و API specification

1. فناوری و محل استقرار Provisioning و دیتابیس Registry چیست؟
2. APIهای customer/business و قرارداد احراز هویت/authorization سرویس‌ها چه هستند؟
3. رویدادها از طریق webhook، message broker یا polling تحویل می‌شوند؟ callback نتیجه به Web2022 چگونه انجام می‌شود؟
4. امضای HMAC یا asymmetric، مدیریت کلید و rotation چگونه پیاده می‌شود؟
5. یک اشتراک جاری برای هر Business قطعی است؟ تغییر پلن، لغو، refund و chargeback چه گذارهایی دارند؟
6. چند کاربر Web2022 می‌توانند یک Customer یا Business را مدیریت کنند؟ انتقال مالکیت و حذف/بایگانی چگونه انجام می‌شود؟
7. entitlement چگونه و با چه مدت اعتبار به Dolibarr می‌رسد و هنگام قطع ارتباط چه می‌شود؟
8. دامنه، DNS/TLS، worker/queue، backup/restore و منابع واقعی میزبان چه محدودیت‌هایی دارند؟
9. آیا bootstrap مدیر اولیه Dolibarr لازم است و چگونه بدون انتقال مالکیت هویت عملیاتی به Provisioning انجام می‌شود؟

تا پاسخ و تصویب این موارد، endpoint نهایی، schema نهایی، migration، سرویس runtime، پرداخت، SSO، read-only enforcement و production rollout اجرا نمی‌شوند.

## ۱۳. معیار پذیرش آینده

- یک Customer با دو Business مستقل ایجاد شود.
- هر Business شناسه پایدار و مستقل داشته باشد.
- خرید Business A فقط Tenant A را provision کند.
- خرید Business B اشتراک و Tenant B جداگانه بسازد.
- تمدید A همان subscription/tenant/DB A را حفظ کند.
- انقضا یا تعلیق A بر B اثر نگذارد.
- رویداد تکراری، دیررس یا خارج از ترتیب منابع تکراری یا وضعیت اشتباه ایجاد نکند.
- شکست و retry پس از هر مرحله ساخت، عملیات را idempotent ادامه دهد.
- دو tenant از نظر DB، filesystem، config، session، URL و دسترسی عملیاتی ایزوله باشند.
- backup و restore هر tenant و Registry مستقل آزموده شود.
- کاربر پس از آماده‌سازی مستقیماً وارد Dolibarr همان Business شود.
- Dolibarr محیط مستقیم عملیات حسابداری/ERP هر tenant است؛ پروژه Accounting حفظ می‌شود و Provisioning را نیز انجام می‌دهد. افزودن Provisioning به معنی بازنشستگی قابلیت‌های فعلی Accounting/Laravel نیست؛ هر تصمیم درباره همزیستی، تغییر یا انتقال یک قابلیت باید جداگانه و با تأیید کاربر انجام شود.
