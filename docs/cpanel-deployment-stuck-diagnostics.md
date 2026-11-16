# عیب‌یابی Deploy متوقف‌شده cPanel برای Accounting

## وضعیت فعلی و محدوده

در 2026-11-16 مالک پروژه گزارش کرد اجرای Deploy در cPanel با پیام زیر در وضعیت «in progress» باقی مانده و به موفقیت نمی‌رسد:

`The deployment that you triggered on Nov 16, 2026 2:58:39 PM is in progress …`

این پیام به‌تنهایی علت توقف یا موفقیت Deploy را مشخص نمی‌کند. در زمان نگارش این سند، دسترسی مستقیم و احراز‌شده به حساب cPanel، UAPI، فایل‌سیستم سرور و log اجرای فوق در اختیار عامل نیست؛ بنابراین log همان اجرا هنوز خوانده نشده و علت قطعی اعلام نمی‌شود.

این سند فقط راهنمای تشخیص است. هیچ فرمانی در سرور اجرا نشده و هیچ تغییری در `.cpanel.yml`، production، دیتابیس، DNS، تنظیمات cPanel یا فایل‌های روی سرور اعمال نشده است.

## وضعیت مخزن و مسیر فعلی Deploy

- مخزن GitHub: `media-store-rtl/accounting`
- مسیر repository در cPanel که در تنظیمات deployment ثبت شده: `/home/mediast1/repositories/accounting`
- مسیر مقصد Laravel: `/home/mediast1/accounting/`
- فایل deploy در ریشه مخزن: `.cpanel.yml`
- این فایل Laravel را deploy می‌کند؛ workflow نصب Dolibarr نیست.
- فایل `package-lock.json` در ریشه مخزن وجود ندارد؛ بنابراین فرمان فعلی `npm install` بدون lockfile اجرا می‌شود.
- فرمان‌های اصلی فعلی به‌ترتیب کپی فایل‌ها، `composer install`، `npm install`، `npm run build`، آماده‌سازی storage، `php artisan optimize:clear`، `php artisan migrate --force` و `php artisan db:seed --class=PermissionSeeder --force` هستند.
- merge در GitHub یا موفقیت CI اثبات نمی‌کند این فرمان‌ها روی cPanel اجرا یا با موفقیت تمام شده‌اند.

## گام اول: یافتن log همان اجرای 2:58:39 PM

در cPanel به **Advanced → Terminal** بروید و با همان کاربر cPanel این فرمان read-only را اجرا کنید:

```bash
uapi --output=jsonpretty VersionControlDeployment retrieve
```

خروجی، فهرست اجراهای Deploy و اطلاعاتی از جمله `log_path`، `repository_root`، شناسه اجرا، وضعیت و timestampها را برمی‌گرداند. رکوردی را پیدا کنید که زمان آن با اجرای **Nov 16, 2026 — 2:58:39 PM** مطابقت دارد. timezone نمایش cPanel را حفظ کنید؛ فقط از روی ساعت UI یک Unix timestamp حدس نزنید.

برای فهرست فایل‌های log اخیر:

```bash
ls -lt /home/mediast1/.cpanel/logs/vc_*_git_deploy.log | head -10
```

سپس فایل دقیق متناظر با همان اجرا را بخوانید:

```bash
tail -n 250 /home/mediast1/.cpanel/logs/vc_<TIMESTAMP>_git_deploy.log
```

به‌جای `<TIMESTAMP>` نام دقیق فایل برگشتی از UAPI را قرار دهید. اگر Terminal در دسترس نیست، در **File Manager** نمایش فایل‌های مخفی را فعال و مسیر `/home/mediast1/.cpanel/logs/` را باز کنید. فایل `vc_*_git_deploy.log` متناظر با زمان را دانلود کنید.

## گام دوم: اگر log متوقف یا ناقص است

این دو منبع فقط برای تشخیص صف و خطاهای cPanel هستند؛ محتوای آن‌ها را بدون بررسی حذف یا پاک‌سازی نکنید:

```bash
tail -n 200 /home/mediast1/.cpanel/logs/user_task_runner.log
```

در صورت دسترسی مدیر سرور، مدیر میزبانی می‌تواند خطاهای عمومی cPanel را نیز در مسیر زیر بررسی کند:

```text
/usr/local/cpanel/logs/error_log
```

اگر UAPI اجرا را همچنان active نشان می‌دهد اما log مدت زیادی تغییر نکرده، زمان آخرین تغییر فایل و وضعیت task runner باید توسط پشتیبانی میزبان بررسی شود. هیچ process یا task را صرفاً به‌دلیل طولانی‌شدن، kill یا حذف نکنید؛ ابتدا باید مشخص شود کدام فرمان فعال است و آیا migration دیتابیس در حال اجراست.

## گام سوم: تشخیص مرحله از روی آخرین خروجی

| آخرین فرمان/نشانه در log | مسیر بررسی بعدی |
|---|---|
| کپی فایل‌ها یا `find` | وجود مسیرهای source/destination، quota، inode و permission |
| `composer install` | نسخه PHP/Composer، دسترسی شبکه به مخزن پکیج‌ها، محدودیت زمان/حافظه و آخرین خروجی Composer |
| `npm install` | نسخه Node/NPM، دسترسی registry، مصرف حافظه و نبود `package-lock.json` |
| `npm run build` | خطای Vite، حافظه Node و آخرین خروجی build |
| `php artisan optimize:clear` | نسخه PHP، bootstrap برنامه و مجوزهای cache/storage |
| `php artisan migrate --force` | اتصال DB، migration مشخص، lock و وضعیت migrationهای ثبت‌شده؛ migration را دوباره یا دستی اجرا نکنید تا وضعیت DB روشن شود |
| `db:seed --class=PermissionSeeder` | خروجی Seeder و idempotency آن؛ اجرای مجدد بدون بررسی ممکن است داده را تغییر دهد |
| هیچ فرمان/خروجی جدیدی ثبت نمی‌شود | صف cPanel، user_task_runner و وضعیت process توسط میزبان بررسی شود |

این جدول فهرست فرضیه‌های تشخیصی است، نه اعلام علت قطعی.

## کارهایی که فعلاً نباید انجام شوند

- دکمه Deploy را چندبار پشت سر هم نزنید و اجرای موازی ایجاد نکنید.
- task فعال را حذف، cancel یا kill نکنید تا log و مرحله دقیق آن مشخص شود.
- migration یا Seeder را دستی دوباره اجرا نکنید.
- `.cpanel.yml` را بدون شواهد log تغییر ندهید؛ این فایل با هر merge به main ممکن است دوباره اجرا شود.
- `APP_KEY`، فایل `.env`، رمز دیتابیس یا secretها را در GitHub، تیکت عمومی یا گفتگو ارسال نکنید.
- فایل `.cpanel.yml` در این مرحله عمداً تغییر نکرده است؛ تغییر بعدی باید بر اساس خطای واقعی log، با logging مرحله‌ای، رفتار خطا و اجرای قابل تکرار طراحی و در PR مستقل تست شود.

## اطلاعات لازم برای بستن عیب‌یابی

برای تشخیص قطعی، این موارد را ارسال کنید:
1. خروجی `uapi --output=jsonpretty VersionControlDeployment retrieve` مربوط به اجرای 2:58:39 PM؛
2. محتوای کامل یا دست‌کم 250 خط پایانی log همان اجرا؛
3. اگر log به‌روز نمی‌شود، 100 تا 200 خط پایانی `user_task_runner.log`.

پیش از ارسال، هرگونه token، password، DSN، secret، ایمیل خصوصی یا داده مشتری را حذف کنید. مسیرهای فایل و نام فرمان‌ها را نگه دارید. پس از دریافت log، علت را به یک task/command مشخص محدود می‌کنیم و فقط همان بخش را اصلاح می‌کنیم.

## منابع رسمی

- [cPanel: Advanced Configuration and Troubleshooting](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-advanced-configuration-and-troubleshooting/) — مسیر logهای deployment و task runner.
- [cPanel Support: آخرین deployment و UAPI retrieve](https://support.cpanel.net/hc/en-us/articles/360052409153-Git-Version-Control-Information-about-the-most-recent-deployment-is-unavailable) — اجرای `VersionControlDeployment retrieve` و دریافت `log_path`.
- [cPanel: Deployment](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/) — رفتار اجرای taskهای `.cpanel.yml`.
