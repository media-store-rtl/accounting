# جریان فعلی خرید اشتراک در Web2022 و مقصد Dolibarr

## وضعیت و دامنه

این سند یافته‌های بررسی کد و تصمیم معماری تأییدشده را ثبت می‌کند؛ گزارش اجرای production یا تأیید پرداخت زنده نیست. طبق تصمیم کاربر، Web2022 رابط مشتری برای ایجاد/مدیریت کسب‌وکارها و مالک خرید، پرداخت، سفارش و وضعیت تجاری اشتراک است. این تصمیم تضمین نمی‌کند که فناوری یا مخزن Web2022 برای همیشه حفظ شود؛ Registry پایدار باید مستقل از آن باقی بماند.

معماری مقصد Dolibarr-first برای عملیات روزمره حسابداری/ERP هر tenant است؛ Dolibarr مرجع کاربران/مجوزهای عملیاتی و محل داده‌های عملیاتی هر کسب‌وکار است. پروژه Accounting موجود حفظ می‌شود و قابلیت Provisioning درون خود آن قرار می‌گیرد. برنامه Laravel فعلی بخشی از Accounting است؛ افزودن Provisioning به معنی کنارگذاشتن یا بازنشسته‌کردن داشبورد و قابلیت‌های فعلی Accounting نیست. مرز همزیستی و تقسیم مسئولیت هر قابلیت هنوز نیازمند تصمیم مستقل است.

## جریان فعلی مشاهده‌شده در Web2022

### ۱. انتخاب پلن و سبد خرید

- مسیرهای نمایش پلن در `routes/web.php` عبارت‌اند از `/accounting-plan` و `/accounting-plan/{slug}`.
- `CartController` برای مدل `AccountingSubscriptionPlan` پلن فعال را به سبد خرید اضافه می‌کند.
- خرید اشتراک به‌صورت آیتمی مستقل در سبد خرید مدیریت می‌شود.

### ۲. پرداخت و شارژ کیف پول

- در `OrderController::store()`، اگر سبد خرید شامل پلن حسابداری باشد، مسیر اختصاصی خرید اشتراک اجرا می‌شود.
- این مسیر خرید اشتراک را مستقیماً با درگاه بانکی تسویه نمی‌کند؛ اگر موجودی کیف پول کافی نباشد، خرید متوقف می‌شود و پیام راهنمای شارژ کیف پول نمایش داده می‌شود.
- callbackهای بانکی در `PaymentController::verify()` قرار دارند و برای چند درگاه، تأیید سمت سرور و ثبت رکورد `Deposit` را انجام می‌دهند. جزئیات و کیفیت کنترل نتیجه درگاه بین شاخه‌های مختلف کد یکسان نیست.
- **نکته نیازمند اصلاح/ممیزی پیش از اتکا:** در شاخه‌های Behpardakht و Sepehr کد `bpVerifyRequest` را فراخوانی می‌کند، اما در مسیرهای بررسی‌شده نتیجه `return` سرویس SOAP پیش از ثبت `status = 4` به‌صورت صریح با مقدار موفقیت مقایسه نمی‌شود. این موضوع باید جداگانه با مستندات درگاه و تست رگرسیون بررسی شود؛ بنابراین این سند ادعا نمی‌کند همه مسیرهای شارژ کیف پول در وضعیت فعلی به‌طور کامل و یکسان امن/تأییدشده‌اند.
- مسیر دیگری از callback برای درگاه سامان، `ResultCode == '0'` پاسخ VerifyTransaction را بررسی می‌کند و سپس وضعیت Deposit را موفق ثبت می‌کند.

### ۳. ثبت خرید اشتراک با کیف پول

در `OrderController::purchaseAccountingSubscriptions()`:

1. نوع و فعال‌بودن پلن‌ها بررسی می‌شود.
2. موجودی کیف پول با مبلغ خرید مقایسه می‌شود.
3. در یک `DB::transaction` سفارش و آیتم‌های سفارش ایجاد می‌شوند.
4. برای هر پلن، رکورد `AccountingSubscription` بر اساس `user_id` و `accounting_subscription_plan_id` پیدا یا ایجاد می‌شود.
5. وضعیت اشتراک `active` و زمان شروع/انقضا ثبت می‌شود؛ تمدید اشتراک معتبر قبلی، تاریخ انقضای آن را جلو می‌برد.
6. رکورد پرداخت Web2022 با وضعیت موفق ثبت می‌شود.
7. پس از commit تراکنش، اعلان‌های سفارش/پرداخت dispatch می‌شوند، سبد خرید پاک می‌شود و کاربر به `order.index` هدایت می‌شود.

این کد مسیر فعلی فعال‌سازی اشتراک Web2022 را نشان می‌دهد؛ به‌خودی‌خود provisioning یا ایجاد tenant در Dolibarr را انجام نمی‌دهد.

## جریان فعلی SSO بین Web2022 و Laravel Accounting

- در Web2022 مسیر درخواست ورود به حسابداری، اشتراک فعال را جست‌وجو می‌کند و یک توکن کوتاه‌عمر و یک‌بارمصرف ایجاد می‌کند؛ عمر گزارش‌شده در کد بررسی‌شده دو دقیقه است.
- Web2022 مرورگر را به مسیر شروع SSO در Accounting هدایت می‌کند.
- `AccountingSsoController::start()` در مخزن Accounting درخواست را به endpoint شروع SSO در Web2022 می‌فرستد.
- `AccountingSsoController::callback()` توکن را با endpoint مبادله Web2022 و secret فعلی مبادله می‌کند.
- callback فعلی در Laravel کاربر/شرکت/عضویت و entitlementهای Laravel را ایجاد یا به‌روزرسانی می‌کند، session لاراول را برقرار می‌کند و در پایان به `/dashboard` لاراول می‌رود.
- این مسیر، مسیر ورود به Dolibarr نیست و نباید با تغییر ساده مقصد redirect به‌عنوان اتصال آماده Dolibarr استفاده شود. قرارداد و secret فعلی SSO نیز بدون بازبینی امنیتی و تصویب مستقل نباید دوباره استفاده شوند.

## شکاف‌های قطعی میان وضع فعلی و مقصد محصول

1. **اشتراک به tenant مستقل Dolibarr متصل نیست.** مدل فعلی `AccountingSubscription` بر اساس `user_id` و `accounting_subscription_plan_id` است؛ در مسیر بررسی‌شده نگاشت پایدار به tenant key، دیتابیس Dolibarr یا فضای سازمانی مستقل دیده نشد.
2. **خرید موفق به provisioning متصل نیست.** مسیر خرید به صفحه سفارش‌های Web2022 ختم می‌شود و tenant ایجاد/بازیابی نمی‌کند.
3. **ورود فعلی به Laravel ختم می‌شود.** ساخت کاربر و session در Laravel انجام می‌شود و redirect نهایی `/dashboard` است.
4. **مالکیت داده و مرجع اشتراک باید روشن شود.** Web2022 در حال حاضر صفحه خرید/پرداخت و مدل اشتراک را در اختیار دارد؛ منبع حقیقت نهایی entitlement و قرارداد اعلام آن به Dolibarr هنوز باید تصویب شود.
5. **پرداخت و provisioning باید مستقل و قابل بازیابی باشند.** پرداخت موفق باید با شناسه سفارش/اشتراک قابل تطبیق باشد؛ provisioning باید idempotent و قابل retry باشد تا retry یا callback تکراری tenant یا اشتراک تکراری نسازد.

## جریان هدف تأییدشده

1. مشتری از رابط Web2022 درخواست ایجاد کسب‌وکار می‌دهد. Backend Web2022 با API امن Provisioning، customer/business را در Registry ایجاد یا بازیابی می‌کند و `business_id` پایدار می‌گیرد.
2. مشتری همان کسب‌وکار را در Web2022 انتخاب می‌کند و پلن را از روش پرداخت موجود (از جمله کیف پول) می‌خرد.
3. Web2022 فقط پس از commit قطعی خرید و ثبت قابل ممیزی، رویداد اشتراک را از طریق Outbox منتشر می‌کند؛ callback بازگشت مرورگر به‌تنهایی اثبات پرداخت نیست.
4. Provisioning امضا، زمان، event ID و aggregate version را اعتبارسنجی و رویداد را در Inbox ثبت می‌کند؛ عملیات باید idempotent باشد.
5. برای خرید اول، tenant اختصاصی شامل DB، config، فایل اسناد، session context و runtime مستقل آماده می‌شود. برای تمدید، همان subscription و tenant/DB حفظ و دوره اعتبار به‌روزرسانی می‌شود.
6. نتیجه provisioning ثبت می‌شود. اگر provisioning موقتاً شکست بخورد، سفارش دوباره شارژ نمی‌شود؛ کار باید قابل retry و reconciliation باشد و Web2022 بتواند وضعیت آماده‌سازی را نمایش دهد.
7. پس از آماده‌شدن tenant، کاربر مستقیماً وارد Dolibarr همان کسب‌وکار می‌شود. SSO به Dolibarr هنوز تصمیم و طراحی مستقل می‌خواهد؛ secret و مسیر SSO فعلی Laravel بازاستفاده نمی‌شود.

### مسئولیت‌های پیشنهادی

| جزء | مسئولیت پیشنهادی |
|---|---|
| Web2022 | رابط ایجاد/مدیریت کسب‌وکار؛ کاتالوگ پلن، checkout، کیف پول/درگاه، سفارش و منبع حقیقت وضعیت تجاری اشتراک |
| Accounting (قابلیت Provisioning) | Registry canonical مشتری/کسب‌وکار/tenant؛ تولید شناسه‌های پایدار؛ ساخت/بازیابی محیط و ثبت وضعیت provisioning |
| Dolibarr tenant | هویت عملیاتی، کاربران، نقش‌ها، مجوزها و تمام عملیات حسابداری/ERP |
| برنامه Laravel موجود | بخش فعلی پروژه Accounting که حفظ می‌شود؛ میزبانی Provisioning در همین پروژه، بدون بازنشستگی خودکار قابلیت‌های موجود |

این جدول مرز مسئولیت معماری تأییدشده است؛ قرارداد فنی و تغییر عملیاتی هنوز تصویب/پیاده‌سازی نشده‌اند.

## ارجاع طراحی داخلی

مرزبندی داخلی پروژه Accounting، API پیشنهادی Web2022، event contract، idempotency، چرخه عمر tenant و مراحل پیاده‌سازی در [طراحی داخلی ماژول Provisioning در Accounting](accounting-provisioning-module-design.md) تشریح شده است. آن سند نیز طرح مستنداتی است و به معنی پیاده‌سازی endpoint یا worker نیست.

## تصمیم‌های باز و محدودیت‌ها

- Web2022 در معماری فعلی رابط مشتری برای ایجاد/مدیریت کسب‌وکار و مالک checkout/پرداخت/وضعیت تجاری اشتراک است؛ جایگزینی احتمالی Web2022 در آینده نباید Registry canonical را جابه‌جا یا شناسه‌ها را عوض کند.
- یک Customer می‌تواند چند Business داشته باشد؛ هر Business اشتراک و tenant مستقل دارد. Provisioning مالک `customer_id`، `business_id` و `tenant_id` است؛ Web2022 مالک `subscription_id` است. شناسه‌های داخلی Web2022 فقط external reference هستند و email کلید canonical نیست.
- Web2022 منبع حقیقت پرداخت و وضعیت تجاری اشتراک است؛ تعریف دقیق گذارهای لغو/تعلیق، refund/chargeback و زمان اثر آن‌ها هنوز باید مشخص شود.
- قرارداد API/event، schema نسخه‌بندی‌شده، امضای درخواست، جلوگیری از replay، idempotency، کنترل ترتیب، retry، ثبت audit، callback/polling وضعیت و مدیریت secretها هنوز طراحی نهایی نشده‌اند.
- provisioning، ایجاد DB یا tenant، SSO به Dolibarr، entitlement enforcement و سیاست read-only در این تغییر پیاده‌سازی نمی‌شوند.
- هیچ دیتابیس، سرور، cPanel، DNS، پرداخت واقعی یا production بررسی/تغییر داده نشده است.

## فایل‌های کد بررسی‌شده

### مخزن Web2022 — `media-store-rtl/media-store-rtl`

- `routes/web.php`
- `app/Http/Controllers/AccountingSubscriptionPlanController.php`
- `app/Http/Controllers/CartController.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Models/AccountingSubscription.php`
- `app/Models/Cart.php`
- `app/Http/Utilities/PaymentCreate.php`

### مخزن Accounting — `media-store-rtl/accounting`

- `routes/web.php`
- `app/Http/Controllers/AccountingSsoController.php`
- `app/Http/Controllers/AuthController.php`
- `config/services.php`
- `docs/dolibarr-integration-boundary.md`
- `PROJECT_DEFINITION.md`

این فهرست، فایل‌های خوانده‌شده در بررسی است و به معنی audit کامل همه مسیرهای پرداخت، webhookها، jobها یا تنظیمات production نیست.


## تصمیم معماری تأییدشده: چند کسب‌وکار برای هر مشتری

- یک مشتری می‌تواند چند کسب‌وکار مستقل را از رابط Web2022 ایجاد و مدیریت کند.
- Provisioning Registry canonical مشتری و کسب‌وکار را نگه می‌دارد و شناسه پایدار `customer_id` و `business_id` تولید می‌کند. Web2022 این شناسه‌ها را دریافت و برای سفارش‌ها/اشتراک‌ها نگهداری می‌کند.
- هر کسب‌وکار اشتراک مستقل در Web2022 و tenant مستقل Dolibarr در Provisioning دارد. DB، config، documents، session و runtime هر tenant مستقل است.
- خرید یا تمدید Business A نباید اشتراک یا محیط Business B را تغییر دهد. تمدید همان subscription و tenant قبلی را حفظ می‌کند.
- Provisioning درون همان پروژه Accounting قرار دارد و Registry و jobهای خودش را به‌صورت ماژولار نگهداری می‌کند؛ معماری نباید یک سرویس/پروژه مستقل دیگر بسازد. جداسازی داده‌ها و sessionهای کنترل‌پلین از tenantهای Dolibarr الزامی است.
- ارتباط Web2022 به Provisioning از Backend و با احراز هویت سرویس‌به‌سرویس است؛ مرورگر مشتری مستقیماً API داخلی Provisioning را فراخوانی نمی‌کند.
- رویدادهای خرید/تمدید پس از commit تجاری از Outbox ارسال و در Inbox Provisioning با event ID یکتا ثبت می‌شوند. کنترل نسخه aggregate، idempotency، retry و reconciliation لازم است.
- پس از آماده‌شدن tenant، مشتری مستقیماً وارد Dolibarr همان کسب‌وکار می‌شود؛ Provisioning واسطه درخواست‌های روزمره نیست.

## تصمیم‌های فنی که همچنان باز هستند

فناوری و محل اجرای Provisioning، schema نهایی Registry، endpointها و event payload، روش امضا و مدیریت کلید، transport/callback یا polling، worker/queue و منابع cPanel، سیاست مالکیت چندکاربره، تغییر/انتقال مالکیت Business، اشتراک هم‌زمان، اثر refund/chargeback و entitlement propagation هنوز تصمیم نهایی ندارند. این مستندات به‌تنهایی مجوز runtime implementation، read-only enforcement، SSO، database creation یا production rollout نیستند.
