# وضعیت پیشرفت پروژه

این فایل وضعیت **پیاده‌سازی فعلی** پروژه را ثبت می‌کند.  
منبع تعریف نیازمندی‌ها و دامنه پروژه: `PROJECT_DEFINITION.md`.

## وضعیت‌ها
- ✅ تکمیل‌شده
- 🟡 در حال انجام
- ⬜ شروع‌نشده
- 🔴 نیازمند اصلاح / مسدود

## 1. ورود و داشبورد
- ✅ ورود کاربر از Web2022 به My Media — طبق وضعیت اعلام‌شده در روند توسعه
- ✅ ورود به داشبورد Accounting — طبق وضعیت اعلام‌شده در روند توسعه

## 2. اشتراک و مالک حساب
- 🟡 اتصال و بررسی وضعیت اشتراک هنگام ورود به Accounting
- 🟡 تعریف مالک حساب به‌عنوان خریدار/پرداخت‌کننده پلن
- 🟡 اعمال سقف تعداد کاربران بر اساس پلن
- ⬜ مدیریت کاربران توسط مالک حساب
- ⬜ نقش‌ها و سطح دسترسی
- ⬜ اعمال قطعی مجوزها در سمت سرور
- ⬜ امکان تمدید پلن/اشتراک از داخل Accounting با انتقال کاربر به Web2022
- ⬜ دریافت نتیجه پرداخت و تمدید اشتراک از Web2022 در Accounting

## ۲.۱. یافته‌های بررسی زیرساخت Web2022
- 🟡 بررسی شد: مخزن Web2022 برابر `media-store-rtl/media-store-rtl` است و زیرساخت واقعی اشتراک Accounting در آن وجود دارد.
- 🟡 بررسی شد: مدل‌های `AccountingSubscriptionPlan` و `AccountingSubscription` در Web2022 وجود دارند.
- 🟡 بررسی شد: پلن‌ها مشخصاتی مانند قیمت، `duration_days` و `max_users` دارند.
- 🟡 بررسی شد: خرید اشتراک Accounting در Web2022 از مسیر کیف پول انجام می‌شود.
- 🟡 بررسی شد: Web2022 برای شارژ کیف پول زیرساخت درگاه‌های بانکی موجود، از جمله Behpardakht، Sepehr و Saman، دارد.
- 🟡 بررسی شد: منطق تمدید اشتراک در Web2022 از قبل وجود دارد؛ اگر اشتراک فعال باشد، دوره جدید به `expires_at` فعلی اضافه می‌شود و اگر اشتراک منقضی/غیرفعال باشد، دوره جدید از زمان فعلی شروع می‌شود.
- 🟡 نتیجه معماری: برای تمدید اشتراک، ساخت درگاه پرداخت جدید در Accounting لازم نیست و Accounting باید از زیرساخت موجود Web2022 استفاده کند.
- 🟡 بررسی Accounting: مقدار `services.web2022.sso_secret` در محیط بررسی‌شده وجود دارد و URL برابر `https://web2022.ir` است.
- 🟡 بررسی Accounting: `services.web2022.sso_secret` و `services.accounting.sso_secret` در محیط بررسی‌شده به یک مقدار یکسان resolve شدند.
- 🟡 بررسی Accounting: مسیرهای SSO زیر در برنامه ثبت شده‌اند: `POST accounting/sso/exchange`، `GET|HEAD accounting/sso/logout` و `GET|HEAD accounting/sso/start`.
- ⬜ تکمیل جریان انتقال کاربر از Accounting به مسیر خرید/تمدید Web2022
- ⬜ تکمیل بازگشت/استعلام وضعیت اشتراک پس از تمدید در Accounting
- ⬜ نهایی‌سازی رابطه Subscription با سال مالی
- ⬜ نهایی‌سازی سناریوی خرید پلن جدید و ارتباط آن با ایجاد سال مالی جدید

## 3. راه‌اندازی اولیه
- ⬜ بررسی اشتراک قبل از ایجاد سال مالی
- ⬜ تعریف سال مالی
- ⬜ ثبت نام سال مالی و اطلاعات پایه در پایگاه داده
- ⬜ تاریخ شروع سال مالی توسط کاربر وارد می‌شود؛ مقدار پیش‌فرض می‌تواند تاریخ روز باشد
- ⬜ تاریخ پایان سال مالی توسط کاربر وارد می‌شود
- ⬜ حذف واحد پول از فرم سال مالی؛ واحد پول به‌عنوان مشخصه سال مالی ثبت نمی‌شود
- ⬜ محدودیت ایجاد سال مالی برای حساب بدون اشتراک فعال
- ⬜ هر پلن فقط امکان ایجاد یک سال مالی را دارد
- ⬜ دسترسی به اطلاعات و گزارش‌های قبلی پس از انقضای اشتراک

### قواعد تأییدشده برای ایجاد سال مالی
- کاربر پس از ورود به داشبورد ابتدا باید وضعیت پلن/اشتراک بررسی شود.
- فقط حساب دارای اشتراک فعال می‌تواند سال مالی ایجاد کند.
- هر پلن فقط یک سال مالی دارد.
- کاربر می‌تواند تاریخ شروع و تاریخ پایان سال مالی را وارد کند.
- تاریخ روز به‌عنوان مقدار پیش‌فرض تاریخ در نظر گرفته می‌شود، اما کاربر امکان تغییر آن را دارد.
- نام سال مالی اجباری است.
- واحد پول در فرم ایجاد سال مالی وجود ندارد و ثبت نمی‌شود.
- اطلاعات سال مالی باید در پایگاه داده ثبت شود.
- حساب دارای اشتراک منقضی می‌تواند به داده‌ها و گزارش‌های قبلی دسترسی داشته باشد، اما برای ادامه استفاده از امکاناتی که نیازمند اشتراک فعال هستند باید اشتراک را تمدید کند.
- تمدید اشتراک نباید باعث ایجاد سال مالی جدید شود؛ همان سال مالی موجود ادامه پیدا می‌کند.
- اگر اشتراک کاربر منقضی شود، کاربر برای ادامه استفاده باید از مسیر تمدید اشتراک اقدام کند.
- فرآیند پرداخت در Accounting انجام نمی‌شود؛ Accounting باید کاربر را برای خرید/تمدید به Web2022 هدایت کند، چون درگاه پرداخت در Accounting وجود ندارد.
- پس از پرداخت موفق در Web2022، وضعیت اشتراک باید به Accounting منتقل/قابل‌استعلام باشد تا دسترسی کاربر دوباره فعال شود.
- مدت و سقف تمدید باید با توجه به تاریخ پایان سال مالی و قواعد پلن تعیین شود؛ جزئیات دقیق این قاعده هنوز نیاز به نهایی‌سازی دارد.

### ۳.۱. تعاریف پایه

پس از ورود به داشبورد و راه‌اندازی اولیه، منوی «تعاریف» به‌عنوان لایه داده‌های پایه قبل از شروع فرآیندهای سفارش، تأمین و تولید طراحی می‌شود.

ترتیب فعلی منوی تعاریف:

1. ⬜ اطلاعات شرکت / مجموعه
2. ⬜ سال‌های مالی
3. ⬜ پرسنل
4. ⬜ کاربران
5. ⬜ دسترسی کاربران
6. ⬜ تأمین‌کنندگان
7. ⬜ انبارها
8. ⬜ قسمت‌های تولید
9. ⬜ کالاها
10. ⬜ عملیات روی کالا

قواعد فعلی:
- ⬜ اطلاعات شرکت/مجموعه به‌عنوان اطلاعات پایه حساب قابل ثبت و ویرایش باشد.
- ⬜ فرم سال مالی شامل نام، تاریخ شروع و تاریخ پایان است و واحد پول در آن ثبت نمی‌شود.
- ⬜ «کاربران» و «دسترسی کاربران» دو بخش مستقل هستند.
- ⬜ تعاریف پایه پیش از ورود عملیاتی به سفارش، تأمین، انبار و تولید تکمیل یا در صورت نیاز قابل تکمیل باشند.
- ⬜ جزئیات فیلدهای هر تعریف هنگام طراحی همان فرم نهایی می‌شود.

## 4. پشتیبان‌گیری و بازیابی
- 🟡 پیاده‌سازی شد: ایجاد Backup به‌صورت فایل Logical JSON با manifest، checksum و schema hash
- 🟡 پیاده‌سازی شد: بارگذاری Backup با اعتبارسنجی format/schema metadata و محدودیت حجم
- 🟡 پیاده‌سازی شد: Restore به‌عنوان عملیات مستقل با checksum/schema validation و تأیید صریح `RESTORE`
- 🟡 پیاده‌سازی شد: مجوزهای جداگانه برای مشاهده، ایجاد، بارگذاری و Restore Backup
- 🟡 تست‌های Feature برای create/upload/restore و authorization اضافه شد؛ اجرای نهایی تست‌ها روی محیط واقعی/CI هنوز باید انجام شود.

## 5. ورود اطلاعات
- 🟡 پیاده‌سازی شد: Import Excel با PhpSpreadsheet، تشخیص ستون‌ها و نمایش نمونه
- 🟡 پیاده‌سازی شد: Column mapping جدا از ذخیره
- 🟡 پیاده‌سازی شد: validation سطح ردیف، required/numeric/unique-code/foreign-key checks
- 🟡 پیاده‌سازی شد: ذخیره فقط پس از validation کامل و داخل transaction
- 🟡 فایل نامعتبر و mapping نامعتبر reject می‌شود
- 🟡 تست‌های Feature برای mapping و validation failure اضافه شد؛ اجرای نهایی تست‌ها روی محیط واقعی/CI هنوز باید انجام شود.

## 6. سفارش
- 🟡 فرم/UI ثبت سفارش هنوز در محدوده این چت است؛ endpoint عملیاتی ثبت سفارش پیاده شد
- ✅ مشتری، تاریخ، اقلام و مقادیر در schema و endpoint ثبت شد
- ✅ اعلان سفارشِ دارای کسری به نقش دارای permission تولید پیاده شد
- ✅ موجودی انبار قبل از تعیین مسیر سفارش بررسی و کسری در OrderItem ثبت می‌شود

## 7. تأمین و خرید
- ✅ درخواست تأمین و محاسبه موجودی/کسری
- ✅ انتخاب تأمین‌کننده در خرید و محدودسازی خرید به کسری واقعی
- ✅ ثبت خرید و اطلاعات فاکتور
- ✅ ثبت حمل و سایر هزینه‌های مستقیم به‌صورت traceable در PurchaseDirectCost
- ✅ ورود و تأیید کالا در انبار با transaction و InventoryMovement
- ✅ اعلان مبلغ ریالی خرید/هزینه مستقیم به کاربران دارای permission مالی

## 8. تولید
- ⬜ درخواست و تحویل مواد از انبار به تولید
- ⬜ ثبت تحویل‌دهنده و تحویل‌گیرنده
- ⬜ مراحل تولید ماژولار
- ⬜ تبدیل/تغییر جنس و مقدار در هر مرحله
- ⬜ ثبت ضایعات در همان عملیات تولید
- ⬜ ثبت کارکرد نیروی انسانی
- ⬜ تأیید روزانه توسط سرپرست تولید

## 9. کالای ساخته‌شده
- ⬜ تحویل محصول نهایی به انبار
- ⬜ ثبت مقدار محصول نهایی
- ⬜ تأیید دریافت توسط انباردار
- ⬜ اعلان موجودی محصول به فروش

## 10. فروش
- ⬜ ثبت فروش
- ⬜ کنترل موجودی کالای ساخته‌شده
- ⬜ صدور حواله خروج
- ⬜ اطلاعات مشتری و مقدار تحویل
- ⬜ اطلاعات وسیله حمل در صورت وجود
- ⬜ ثبت تحویل توسط انبار

## 11. بهای تمام‌شده
- ⬜ مواد و اقلام مصرف‌شده
- ⬜ بهای ریالی خرید
- ⬜ دستمزد
- ⬜ هزینه‌های مستقیم مانند حمل
- ⬜ ضایعات
- ⬜ محاسبه بهای تمام‌شده در سطح سفارش
- ⬜ محاسبه بهای تمام‌شده در سطح محصول

## 12. گزارش‌ها
- ⬜ گزارش بهای تمام‌شده
- ⬜ گزارش قابل‌ردگیری از اجزای هزینه
- ⬜ گزارش در سطح سفارش و محصول
- ⬜ گزارش‌های مدیریتی تکمیلی

## 13. وضعیت SSO و Logout
- 🟡 بررسی و اصلاح جریان Logout بین Accounting و Web2022
- 🟡 بررسی مسیرهای `accounting/sso/start`، `exchange` و `logout`
- 🟡 بررسی رفتار Logout موفق و بازگشت به مقصد صحیح
- 🟡 بررسی انجام شد: در Accounting مسیر `GET|HEAD accounting/sso/logout` ثبت است، اما مشکل گزارش‌شده همچنان نیازمند ردیابی در جریان واقعی Logout و مقصد Web2022 است.
- 🟡 بررسی انجام شد: ساختار مورد انتظار توکن SSO شامل `user_id | timestamp | nonce | signature` و امضای HMAC-SHA256 بوده است؛ درخواست واقعی قبلی در Web2022 با `403` مواجه شده بود.
- 🟡 بررسی انجام شد: در Accounting درایور Session برابر `file`، نام Cookie برابر `accounting_session` و Domain برابر `null` است.
- 🟡 نتیجه فعلی: هنوز علت نهایی `404` در Web2022 و ارتباط آن با Logout/SSO اثبات نشده است و نباید به‌عنوان رفع‌شده ثبت شود.

- 🟢 تأییدشده و تست‌شده: در سناریوی اولین خرید، کاربر ابتدا از Web2022 وارد My Media و سپس Accounting می‌شود؛ در اولین ورود، کاربر Accounting ایجاد می‌شود و ایمیل اطلاع‌رسانی ایجاد کاربر و کد/اطلاعات مربوط برای او ارسال می‌شود.
- 🟢 تأییدشده و تست‌شده: Logout از Accounting باید **دوطرفه** باشد؛ یعنی خروج از Accounting هم‌زمان/در ادامه کاربر را از Web2022 نیز خارج کند. این رفتار عمدی و مورد انتظار پروژه است.
- 🟢 تأییدشده: Logout معمولی داخل Web2022 یک جریان مستقل از Cross-App Logout مربوط به Accounting است.
- 🟡 نکته بررسی: با وجود تأیید موفق سناریوی مورد انتظار در تست، در Production یک GET|HEAD accounting/sso/logout در route list مشاهده شده که با وضعیت فعلی کد Accounting در GitHub یکسان نیست؛ بنابراین علت 404 گزارش‌شده همچنان باید در تطبیق Revision/Deployment و جریان واقعی Redirect ردیابی شود.

## 14. قواعد ثبت پیشرفت
- هر مورد فقط پس از مشاهده یا تأیید شواهد پیاده‌سازی به وضعیت ✅ منتقل شود.
- وضعیت‌های 🟡 و 🔴 باید همراه با توضیح کوتاه درباره کار باقی‌مانده یا مانع ثبت شوند.
- تصمیم‌ها و نیازمندی‌های جدید باید در همین فایل ثبت شوند تا مشخص باشد تا کجا پیش رفته‌ایم.
- تغییرات مهم پروژه باید با commit قابل‌ردگیری باشند.


---

# 14. Final Integration / QA — 2026-10-06

## قاعده QA
این بخش فقط بر اساس evidence موجود در Repository/کد بررسی‌شده ثبت شده است. در این بررسی هیچ موردی صرفاً به دلیل وجود Migration، Model یا فایل کد به وضعیت ✅ منتقل نشده است.

## وضعیت واقعی در پایان این QA

| بخش | وضعیت | Evidence / Gap |
|---|---|---|
| Web2022 → Accounting / Login | 🟡 | AccountingSsoController و مسیرهای /sso/start و /sso/callback در GitHub وجود دارند؛ اجرای واقعی E2E در این جلسه انجام نشد. |
| Subscription status | 🟡 | callback به Web2022 و دریافت subscription_id وجود دارد، اما کنترل عملیاتی فعال/منقضی بودن اشتراک در Accounting و تست آن اثبات نشده است. |
| Dashboard | 🟡 | DashboardController وجود دارد و Company/FiscalYear را می‌خواند؛ تست HTTP واقعی انجام نشده است. |
| Company setup | ⬜ | در routeهای فعلی GitHub مسیر CRUD/Setup برای Company مشاهده نشد؛ فقط Dashboard به Company موجود وابسته است. |
| Fiscal year | ⬜ | Migration/Model dependency وجود دارد، اما route/controller/form و تست ایجاد/بستن سال مالی evidence نشده است. |
| Personnel / Users / Access | ⬜ | ساختار User/Personnel در schema دیده می‌شود، اما CRUD، مدیریت مالک حساب، Role/Permission و enforcement سمت سرور evidence نشده است. |
| Supplier / Warehouse / Product | 🟡 | Migrationهای مربوط به Supplier، Goods/Product، Locations و سایر schemaها وجود دارند؛ implementation عملیاتی و تست CRUD/permission evidence نشده است. |
| Order | ⬜ | implementation عملیاتی و route/controller قابل‌اثبات برای ثبت سفارش، موجودی و اعلان تولید مشاهده نشد. |
| Supply / Purchase | ⬜ | schemaهای مرتبط در repository وجود دارند، اما flow عملیاتی درخواست تأمین، خرید، دریافت و هزینه مستقیم تست/اثبات نشده است. |
| Production | 🟡 | schemaهای Production/Route/Stage/Run/Input/Output/Scrap وجود دارند؛ execution workflow و تأیید روزانه evidence نشده است. |
| Material consumption | ⬜ | schema مربوط به Operation Inputs وجود دارد، اما ثبت/کسر موجودی/ردیابی مصرف در flow واقعی اثبات نشده است. |
| Labor / Machine / Overhead | ⬜ | در این QA implementation عملیاتی و test evidence کافی برای این اجزا مشاهده نشد. |
| Costing | ⬜ | وجود schema به‌تنهایی برای PASS کافی نیست؛ محاسبه واقعی CostCalculation/CostComponent در سطح Order/Product تست نشده است. |
| Finished goods | ⬜ | schema/موجودیت‌های مربوطه در طراحی پروژه وجود دارند، اما receipt، تأیید انبار و movement واقعی تست نشده است. |
| Sales | ⬜ | flow ثبت فروش، کنترل موجودی، DeliveryRequest/Delivery و تحویل تست نشده است. |
| Reports | ⬜ | گزارش‌های واقعی مبتنی بر داده عملیاتی و traceability در این QA اثبات نشده‌اند. |
| Backup / Restore | ⬜ | implementation/test evidence کافی وجود ندارد. |
| Excel Import | ⬜ | implementation/test evidence کافی وجود ندارد. |
| Logout / Cross-App Logout | 🔴 | کد فعلی GitHub فقط local logout را در POST /logout دارد و SSO controller مسیر logout ندارد؛ در حالی که PROJECT_PROGRESS قبلاً مسیر Production accounting/sso/logout را گزارش کرده بود. این mismatch باید قبل از PASS شدن رفع و روی محیط واقعی تست شود. |
| Audit Trail / Notifications | ⬜ | implementation و evidence عملیاتی قابل‌قبول برای flow کامل مشاهده نشد. |

## Route status

Evidence کد فعلی:
- GET /
- GET /login
- POST /login
- GET /logout-success
- GET /sso/start
- GET /sso/callback
- GET /dashboard
- POST /logout

bootstrap/app.php فقط routes/web.php را به‌عنوان web route register می‌کند و route file دیگری برای API در آن ثبت نشده است.

نکته مهم: این فهرست خروجی اجرای php artisan route:list نیست؛ به دلیل نبود دسترسی shell به /home/mediast1/accounting در این جلسه، route:list واقعی اجرا نشد. بنابراین route status محیط Production هنوز 🟡 است.

## Migration status

در Repository Migrationهای schema تا 000030_create_goods_units_table.php مشاهده شد و migrationهای متعدد برای Account/Company/FiscalYear، Supplier، Goods/Product/Material، Location، Production، Production Route/Stage/Run، Operation Input/Output، Scrap، Customer و Goods Units وجود دارند.

با این حال:
- php artisan migrate:status در این جلسه روی /home/mediast1/accounting اجرا نشد.
- php artisan migrate:fresh نیز اجرا نشد.
- بنابراین clean migration execution و وضعیت واقعی دیتابیس Production هنوز تأییدشده نیست.
- وجود Migration به‌تنهایی به معنی PASS شدن feature نیست.

## Test status

- composer.json دارای script تست @php artisan test است.
- فایل‌های استاندارد tests/Feature/ExampleTest.php، tests/Unit/ExampleTest.php و tests/TestCase.php از Repository فعلی قابل بازیابی نبودند.
- php artisan test در این جلسه روی سرور اجرا نشد.
- بنابراین هیچ feature به دلیل «کد موجود است» PASS اعلام نشده است.

## Git / Deployment status

- GitHub repository و branch main قابل بررسی است.
- آخرین commit مشاهده‌شده در این audit: 38c2a607a9f08d22ddd36fcfe7c365714bb830a3 با پیام Add approved customer and goods unit schema.
- وضعیت git status روی /home/mediast1/accounting در این جلسه قابل اجرا نبود؛ بنابراین clean بودن working tree سرور تأیید نشده است.
- تعداد زیاد commitهای schema در بازه کوتاه و commitهای حذف/rebuild migrationها نشان می‌دهد Migration history اخیراً بازسازی شده و باید قبل از deploy نهایی با migrate:status و یک clean migration test تأیید شود.

## نتیجه نهایی QA

در این مرحله پروژه از نظر schema/design progress جلو رفته است، اما از نظر Final Integration / QA هنوز آماده اعلام PASS سراسری نیست.

Blockerهای اصلی:
1. نبود دسترسی shell برای اجرای تست واقعی روی /home/mediast1/accounting.
2. نبود evidence از اجرای php artisan test.
3. نبود evidence از php artisan migrate:status و clean migration run.
4. نبود evidence از php artisan route:list واقعی Production.
5. نبود route/controller evidence برای اکثر flowهای عملیاتی.
6. mismatch بین Logout/SSO مستندشده Production و route/controller فعلی GitHub.
7. نبود E2E evidence برای زنجیره Order → Supply → Production → Costing → Finished Goods → Sales → Reports.

این بخش به‌عنوان QA override ثبت شده و نباید هیچ موردی بدون implementation + evidence/test به وضعیت ✅ منتقل شود.


# 15. Reports / Reporting Scope Audit — 2026-10-07

## Requirement boundary

بررسی مستقیم `PROJECT_DEFINITION.md` نشان می‌دهد فقط این خروجی‌های گزارش‌دهی در محدوده فعلی تعریف شده‌اند:

1. **گزارش بهای تمام‌شده در سطح سفارش**
2. **گزارش بهای تمام‌شده در سطح محصول**
3. نمایش/ردگیری اجزای تشکیل‌دهنده بهای تمام‌شده در ارتباط با سفارش و محصول

گزارش‌های مدیریتی تکمیلی، نمودارها و جزئیات بصری در تعریف پروژه اختیاری/آتی هستند و در این Scope پیاده‌سازی نمی‌شوند.

## Audit result

- **Route:** هیچ route عملیاتی Reports در `routes/web.php` فعلی مشاهده نشد.
- **Controller:** هیچ `ReportController` یا controller گزارش‌دهی قابل‌اثبات در tree فعلی مشاهده نشد.
- **Authorization:** permission اختصاصی Reports در routeهای فعلی evidence نشد.
- **Filters / company / fiscal year / date range:** implementation واقعی گزارش و قرارداد دقیق فیلترها وجود ندارد؛ بنابراین هیچ فیلتر ساختگی به‌عنوان requirement فرض نشد.
- **Data source:** گزارش باید از CostCalculation/CostComponent و داده‌های واقعی زنجیره عملیاتی مصرف کند. در وضعیت فعلی، implementation عملیاتی CostCalculation/CostComponent که بتواند data contract گزارش را تأمین کند evidence نشد.
- **Totals / subtotals:** به دلیل نبود data contract واقعی costing، محاسبه aggregate قابل‌اتکا برای گزارش قابل پیاده‌سازی نیست.
- **Pagination / export:** در `PROJECT_DEFINITION.md` برای این دو گزارش الزام صریحی به pagination یا export تعریف نشده است؛ در نتیجه در این Scope اضافه نمی‌شود.
- **Empty state / validation / performance:** به علت نبود route/controller و منبع داده نهایی، رفتار واقعی این موارد هنوز قابل تست و تأیید نیست.
- **UI:** UI واقعی Reports در repository فعلی evidence نشد.
- **Feature tests:** تست Feature اختصاصی Reports با داده واقعی وجود/اجرا evidence نشد. اجرای `php artisan test` روی محیط محلی/Production این جلسه نیز به علت نبود shell access انجام نشد.

## Decision

این Scope فعلاً **🟡 / blocked by upstream contracts** است و **PASS نیست**. ایجاد یک صفحه نمایشی، query حدسی، داده hard-coded یا ساختن یک Costing engine جدید برای سبز کردن Reports برخلاف requirement است و انجام نشد.

برای PASS شدن Reports ابتدا باید interface/data contract خروجی Costing شامل حداقل شناسه سفارش/محصول، اجزای هزینه، مبالغ، ارتباط traceable با منابع و تاریخ/بازه گزارش نهایی و در upstream Costing فراهم شود؛ سپس همین Scope باید route + authorization + filters + company/fiscal-year context + date handling + aggregation + empty state + validation + performance + Feature tests + UI واقعی را پیاده‌سازی و با داده واقعی تست کند.

## QA evidence limitation

- branch/repository قابل مشاهده از GitHub: `main`
- آخرین commit مشاهده‌شده: `45a5c28d23433583ba6ecc2101f06e014508c3ce` — `fix: deploy composer lock file`
- `git status` واقعی روی `/home/mediast1/accounting` در این محیط قابل اجرا نیست؛ بنابراین clean بودن working tree محلی ادعا نمی‌شود.
- CI Run ID/Result برای این commit از connector موجود قابل بازیابی نبود؛ بنابراین Run ID یا PASS ساختگی ثبت نمی‌شود.
- هیچ Merge یا Deploy انجام نشد.


# 16. Definition / Dashboard Audit — 2026-10-07

در این بررسی، کد فعلی Repository با `PROJECT_DEFINITION.md` و مسیرهای واقعی `main` تطبیق داده شد.

## اصلاحات انجام‌شده
- ✅ داشبورد از اعداد hard-coded خارج شد و آمار واقعی سفارش، موجودی، کسری تأمین، تولید و هزینه‌های ثبت‌شده را از دیتابیس می‌خواند.
- ✅ داشبورد وضعیت اشتراک و سال مالی را نمایش می‌دهد و فقط لینک‌های مجاز کاربر را نشان می‌دهد.
- ✅ زنجیره اصلی پروژه در داشبورد به‌صورت «سفارش → تأمین → انبار → تولید → کالای ساخته‌شده → تحویل» نمایش داده می‌شود و برای بخش‌های بدون UI لینک ساختگی ایجاد نمی‌شود.
- ✅ اطلاعات شرکت از حالت فقط نام/کد خارج شد و فیلدهای پایه تعریف‌شده در پروژه به‌صورت Migration افزایشی، Model، Controller و Form اضافه شدند؛ لوگو در Storage نگهداری می‌شود.
- ✅ ایجاد سال مالی به اشتراک فعال وابسته شد.
- ✅ هم‌زمان بیش از یک سال مالی باز برای یک شرکت مسدود شد.
- ✅ فرم سال مالی مطابق تعریف پروژه به نام، تاریخ شروع و تاریخ پایان محدود شد؛ کد داخلی سال مالی به‌صورت خودکار ساخته می‌شود و تاریخ شروع جدید به‌صورت پیش‌فرض تاریخ روز است.
- ✅ PermissionSeeder با مجوزهای موردنیاز routeهای فعلی تطبیق داده شد.
- ✅ یک خطای rollback در migration هزینه دستمزد که به‌اشتباه جدول `personnel` را حذف می‌کرد اصلاح شد.

## کسری‌های باقی‌مانده و تأییدنشده
- 🟡 UI مستقل برای تأمین، انبار، تولید و کالای ساخته‌شده هنوز کامل نیست.
- 🟡 جریان کامل تولید شامل عملیات، تبدیل، ضایعات و تأیید سرپرست هنوز کامل نیست.
- 🟡 Audit Trail مستقل و اتصال آن به عملیات مهم هنوز کامل نیست.
- 🟡 بهای تمام‌شده کامل هنوز همه اجزای تعریف‌شده، به‌خصوص ضایعات و سربار/هزینه‌های مستقیم قابل تخصیص، را پوشش نمی‌دهد.
- 🟡 UI مستقل Backup/Restore و Excel Import هنوز کامل نیست؛ backend سرویس‌ها وجود دارند.
- 🟡 جریان Cross-App Logout و برخی سناریوهای SSO همچنان نیازمند تست واقعی است.
- 🟡 تست واقعی `php artisan test`, `migrate:status` و E2E روی سرور Production در این محیط قابل اجرا نیست و نباید PASS اعلام شود.

این بخش وضعیت implementation را ثبت می‌کند و هیچ موردی صرفاً به دلیل وجود Migration یا فایل کد، به وضعیت «تکمیل‌شده» منتقل نشده است.


# 17. Full Definition / Frontend + Backend Implementation Audit — 2026-10-07

## هدف این مرحله

از این مرحله به بعد، وضعیت پروژه فقط بر اساس وجود یک Migration، Model، Controller یا فایل UI تعیین نمی‌شود. هر Requirement از `PROJECT_DEFINITION.md` باید در کل زنجیره زیر قابل‌ردگیری باشد:

`Definition → UI/Frontend → Route → Authorization → Controller/Service → Model/Database → Notification/Audit → نتیجه در UI`

بنابراین Frontend، UI/UX و اتصال واقعی آن به Backend جزو معیار تکمیل پروژه هستند.

## Frontend Audit

### وضعیت کلی

- 🟡 ساختار UI موجود است و Dashboard فعلی به داده‌های واقعی متصل شده است، اما پوشش کامل Frontend مطابق تمام حوزه‌های Definition هنوز اثبات نشده است.
- 🟡 UI مستقل و کامل برای همه ماژول‌های تعاریف پایه، سفارش، تأمین، انبار، تولید، کالای ساخته‌شده، فروش/تحویل، بهای تمام‌شده، گزارش‌ها، Backup/Restore و Import هنوز باید به‌صورت End-to-End تطبیق و تکمیل شود.
- 🟡 وجود Route یا Backend به‌تنهایی به معنی وجود Frontend کامل نیست.
- 🟡 وجود صفحه به‌تنهایی نیز PASS محسوب نمی‌شود؛ صفحه باید به عملیات واقعی، داده واقعی، Permission و Validation متصل باشد.
- 🟡 Responsive، RTL، Empty State، Error State، Success Feedback و UX عملیات اصلی باید برای صفحات نهایی به‌صورت مستقل بررسی شوند.
- 🟡 هیچ UI یا Mock ساختگی نباید برای سبز کردن وضعیت یک Requirement به‌عنوان implementation کامل ثبت شود.

## Frontend معیارهای PASS

برای هر صفحه/ماژول، موارد زیر باید قابل‌اثبات باشند:

- ⬜ Route واقعی
- ⬜ Permission مناسب
- ⬜ نمایش داده واقعی Database
- ⬜ فرم واقعی و متصل به Backend
- ⬜ Server-side validation
- ⬜ نمایش خطاهای Validation در UI
- ⬜ Success/Error feedback
- ⬜ Empty state
- ⬜ Loading/disabled state در عملیات لازم
- ⬜ Pagination در صورت نیاز
- ⬜ Search/Filter فقط در صورت تعریف یا نیاز واقعی
- ⬜ Responsive در Desktop/Mobile
- ⬜ RTL و فارسی
- ⬜ عدم وجود داده مهم hard-coded
- ⬜ عدم وجود Mock/Placeholder غیرضروری
- ⬜ اتصال صحیح دکمه‌ها و Actionها به عملیات واقعی
- ⬜ رعایت Permission در UI علاوه بر Enforcement سمت Server
- ⬜ حفظ وضعیت‌ها و نتایج واقعی فرآیند در UI

## تطبیق Frontend با حوزه‌های Definition

| حوزه | Backend | Frontend | End-to-End | وضعیت |
|---|---|---|---|---|
| Company | 🟡 | 🟡 | ⬜ | 🟡 |
| Fiscal Year | 🟡 | 🟡 | ⬜ | 🟡 |
| Users / Personnel | 🟡 | ⬜ | ⬜ | 🟡 |
| Roles / Permissions | 🟡 | ⬜ | ⬜ | 🟡 |
| Customers | 🟡 | ⬜ | ⬜ | 🟡 |
| Suppliers | 🟡 | ⬜ | ⬜ | 🟡 |
| Goods / Units | 🟡 | ⬜ | ⬜ | 🟡 |
| Warehouses / Locations | 🟡 | ⬜ | ⬜ | 🟡 |
| Production Sections | 🟡 | ⬜ | ⬜ | 🟡 |
| Orders / Sales | 🟡 | 🟡 | ⬜ | 🟡 |
| Supply Requests | 🟡 | ⬜ | ⬜ | 🟡 |
| Purchases / Receipts | 🟡 | ⬜ | ⬜ | 🟡 |
| Material Handovers | 🟡 | ⬜ | ⬜ | 🟡 |
| Production | 🟡 | ⬜ | ⬜ | 🟡 |
| Finished Goods | 🟡 | ⬜ | ⬜ | 🟡 |
| Delivery Requests / Deliveries | 🟡 | 🟡 | ⬜ | 🟡 |
| Inventory / Movements | 🟡 | ⬜ | ⬜ | 🟡 |
| Labor | 🟡 | ⬜ | ⬜ | 🟡 |
| Costing | 🟡 | 🟡 | ⬜ | 🟡 |
| Reports | 🟡 | ⬜ | ⬜ | 🟡 |
| Notifications / Approval | 🟡 | 🟡 | ⬜ | 🟡 |
| Audit Trail | 🟡 | ⬜ | ⬜ | 🟡 |
| Backup / Restore | 🟡 | ⬜ | ⬜ | 🟡 |
| Excel Import | 🟡 | ⬜ | ⬜ | 🟡 |
| SSO / Subscription | 🟡 | 🟡 | ⬜ | 🟡 |

> این جدول وضعیت فعلی Audit است، نه اعلام تکمیل. موارد 🟡 باید تا رسیدن به implementation + integration + test evidence بررسی و اصلاح شوند.

## Backend / Database وضعیت فعلی

- 🟡 Migrationهای متعددی برای بخش‌های جدید پروژه اضافه شده‌اند، از جمله Production Output، Sales/Delivery، Material Handover، Inventory Valuation، Labor Cost و Company Profile.
- 🟡 Migration هزینه دستمزد که در `down()` به‌اشتباه `personnel` را حذف می‌کرد اصلاح شده است.
- 🟡 Permissionهای جدید برای بخش‌های عملیاتی و Costing اضافه شده‌اند.
- 🟡 Dashboard و برخی Controller/Serviceها برای جلوگیری از خطای Runtime در صورت نبود جدول‌های جدید Guard دارند؛ این Guards راه‌حل نهایی Migration Drift محسوب نمی‌شوند.
- 🔴 وضعیت واقعی Migrationهای Production هنوز باید با `php artisan migrate:status` و اجرای واقعی Migrationها تأیید شود.
- 🔴 تست واقعی Production/CI هنوز برای کل زنجیره اجرا نشده است.
- 🟡 `.cpanel.yml` برای اجرای `php artisan migrate --force` به‌روزرسانی شده، ولی اجرای موفق آن روی Production هنوز اثبات نشده است.

## Business Flow Audit

زنجیره‌ای که باید تا PASS شدن کامل بررسی شود:

`سفارش → بررسی موجودی → تأمین/تولید → مصرف مواد → تولید → کالای ساخته‌شده → انبار → درخواست تحویل → فروش/تحویل → گزارش بهای تمام‌شده`

و زنجیره هزینه:

`خرید واقعی + هزینه‌های مستقیم + مصرف واقعی مواد + دستمزد + ضایعات + سایر اجزای تعریف‌شده → Costing → گزارش سفارش/محصول`

- 🟡 بخش‌هایی از این زنجیره در Repository پیاده‌سازی شده‌اند.
- 🔴 اجرای کامل End-to-End با داده واقعی هنوز evidence نشده است.
- ⬜ تا زمانی که تمام روابط بین Entityها، موجودی، هزینه‌ها، تولید، فروش و گزارش‌ها تست نشوند، زنجیره کامل PASS نیست.

## قواعد جدید ثبت پیشرفت

1. Frontend بخشی از Requirement است و نباید جدا از Backend ارزیابی شود.
2. وجود Blade/JS/CSS به‌تنهایی implementation محسوب نمی‌شود.
3. وجود API/Controller به‌تنهایی implementation Frontend محسوب نمی‌شود.
4. هر Feature باید از UI تا Database و برگشت نتیجه به UI قابل‌ردگیری باشد.
5. Permission باید هم در UI برای UX و هم در Server برای امنیت اعمال شود.
6. داده‌های مهم نباید hard-coded باشند.
7. Guardهای موقت برای جداول/قابلیت‌های missing نباید جایگزین Migration و Root Cause Fix شوند.
8. هیچ Requirement صرفاً به دلیل وجود schema به وضعیت ✅ منتقل نمی‌شود.
9. برای وضعیت ✅ باید implementation، integration و در حد امکان test evidence وجود داشته باشد.
10. پس از هر سری اصلاحات، Audit مجدد Definition باید انجام شود.

## اولویت فعلی تکمیل

1. 🔴 تطبیق کامل Migrationها با Production و رفع Migration Drift بدون آسیب به داده‌های موجود.
2. 🔴 تکمیل Backend و Frontend تعاریف پایه.
3. 🔴 تکمیل End-to-End سفارش → تأمین/تولید → کالای ساخته‌شده → فروش/تحویل.
4. 🔴 تکمیل Costing واقعی و traceable.
5. 🔴 تکمیل Reports بر اساس Costing واقعی.
6. 🟡 تکمیل Notifications، Approval و Audit Trail.
7. 🟡 تکمیل Backup/Restore و Excel Import در UI و E2E.
8. 🟡 تکمیل SSO/Subscription و تست Cross-App Logout.
9. 🟡 اجرای Test Suite، Migration Status و E2E verification روی محیط قابل‌اجرا.
10. 🟡 Audit نهایی کل `PROJECT_DEFINITION.md` و انتقال فقط موارد دارای evidence به وضعیت ✅.

## معیار نهایی Done

پروژه زمانی «کامل» اعلام می‌شود که هر Requirement صریح `PROJECT_DEFINITION.md` حداقل یکی از این وضعیت‌ها را داشته باشد:

- ✅ implementation کامل + integration/test evidence
- ⏳ نیازمند تصمیم صریح کسب‌وکاری از کاربر

و نباید Requirement پیاده‌نشده با یکی از موارد زیر پنهان شود:

- Guard موقت
- Mock UI
- عدد hard-coded
- route بدون implementation
- controller بدون UI
- migration بدون feature
- UI بدون backend
- permission فقط در Frontend
- گزارش بدون داده واقعی

**این فایل باید همراه با هر تغییر مهم به‌روزرسانی شود و وضعیت واقعی پروژه را ثبت کند، نه صرفاً خوش‌بینانه‌ترین وضعیت ممکن را.**


# 18. Current implementation audit — 2026-10-08

- ✅ Company profile and fiscal-year management are implemented with server-side authorization and database-backed state.
- ✅ Users / Personnel / Roles and company permissions are enforced server-side; user management is permission-managed rather than UI-only.
- ✅ Suppliers / Goods / Units / Locations / Production Sections / Routes / Stages / Operations / Productions have database-backed operational UI and backend flows.
- ✅ Order → stock check → shortage → supply → purchase → receipt and material handover are implemented.
- ✅ FIFO / Weighted Average inventory valuation and labor-cost snapshots are implemented.
- ✅ Finished-goods output is separated into production approval and warehouse receipt.
- ✅ Production operation execution now records operation inputs/outputs, scrap, supervisor review, state transitions and audit before/after data.
- ✅ Costing reports include material cost, labor cost, direct purchase cost, scrap cost, valuation methods and unvalued material quantity.
- ✅ Notification Center and secure Web2022 cross-app logout are implemented.
- ✅ Backup/Restore and Excel Import have user-facing UI and transaction/validation handling.
- ✅ CI now verifies migrations, route registration and the full PHPUnit suite. Latest verification run 37760887899 completed successfully.
- 🟡 Browser E2E and real cPanel deployment have not been executed from this environment. Production deployment remains intentionally unclaimed until cPanel runs the storage-safe deployment configuration and the live smoke checks are performed.
- 🟡 Payment remains outside Accounting; subscription entitlement is consumed from Web2022 as defined by the project scope.

## Deployment safety
The current cPanel deployment configuration excludes .env, storage, public/storage, node_modules and public/build from the repository copy, so deployment does not intentionally overwrite Laravel uploaded storage or the production environment file.
