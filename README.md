# Accounting

مخزن `media-store-rtl/accounting` شامل برنامه Laravel پروژه Accounting و قابلیت‌های فعلی آن است.

## ساختار پروژه

- `app/`: کد برنامه Laravel شامل کنترلرها، مدل‌ها، سرویس‌ها و سیاست‌های دسترسی.
- `database/`: migrationها، seedها و کارخانه‌های داده.
- `resources/`: رابط کاربری، viewها و assetهای برنامه.
- `routes/`: مسیرهای برنامه.
- `tests/`: آزمون‌های خودکار.
- `docs/`: مستندات محصول، عملیات و قابلیت‌های موجود.
- `PROJECT_DEFINITION.md`: تعریف فعلی محصول و مرزهای نگهداری.
- `PROJECT_PROGRESS.md`: تاریخچه تغییرات و وضعیت کارهای تأییدشده.

## وب‌سایت شرکتی فرنام

بخش عمومی وب‌سایت فرنام و قابلیت‌های مدیریت محتوا و سرنخ‌های مشتری در همین برنامه Laravel قرار دارند. این بخش در کنار قابلیت‌های فعلی Accounting نگهداری می‌شود و جایگزین آن‌ها نیست.

## دریافت کد

```bash
git clone https://github.com/media-store-rtl/accounting.git
cd accounting
composer install
```

این مخزن دیگر Dolibarr، کد upstream آن، Git Submodule یا workflow اختصاصی انتشار Dolibarr را در بر نمی‌گیرد. برای clone کردن مخزن نیازی به `--recurse-submodules` یا اجرای `git submodule update` نیست.

## استقرار

تنظیمات فعلی استقرار Laravel در `.cpanel.yml` مستقل از Dolibarr است. هرگونه merge به شاخه اصلی ممکن است با Auto Deployment موجود در cPanel مرتبط باشد؛ بنابراین پیش از merge باید اثر استقرار و وضعیت CI بررسی شود. این مستندات ادعای اجرای deployment یا تغییر production ندارند.
