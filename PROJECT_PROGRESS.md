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
- ⬜ ایجاد Backup
- ⬜ بارگذاری Backup
- ⬜ Restore از Backup
- ⬜ تفکیک شفاف عملیات ایجاد، بارگذاری و بازیابی Backup

## 5. ورود اطلاعات
- ⬜ Import اطلاعات از Excel
- ⬜ تعریف نگاشت ستون‌های Excel به اطلاعات سیستم
- ⬜ اعتبارسنجی اطلاعات قبل از ثبت

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


## 3.2 تعاریف پایه عملیاتی — 2026-10-06
- Migrationهای واقعی موجود در شاخه جاری بررسی شد؛ به‌خصوص schemaهای Supplier، Goods، Locations، Production Sections، Goods Units و همچنین schema جدید workflow که جدول‌های `inventory`، `inventory_movements` و `supplier_goods` را ایجاد می‌کند.
- برای کالا، به‌جای ایجاد موجودیت فرضی Product از موجودیت واقعی repo یعنی `goods` استفاده شد.
- برای انبار، به‌جای ایجاد جدول فرضی Warehouse از `locations` با `type=warehouse` استفاده شد.
- برای قسمت تولید، از رابطه واقعی `production_sections.location_id` استفاده شد و ساختار route/stage/operation تولید خارج از scope این بخش باقی ماند.
- authorization از middleware موجود `permission` و متد `User::hasPermission` استفاده می‌کند؛ company ownership در query/controllerها بررسی می‌شود.
- عملیات موجودی داخل transaction انجام می‌شوند و خروج/انتقال/اصلاح کاهشی موجودی منفی را رد می‌کند.
- تست Feature برای company isolation، ایجاد کالا با وابستگی‌های هم‌شرکت و جلوگیری از موجودی منفی اضافه شد؛ اجرای `php artisan test` روی سرور در این جلسه انجام نشد.
