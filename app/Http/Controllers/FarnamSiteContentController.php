<?php

namespace App\Http\Controllers;

use App\Models\FarnamSiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarnamSiteContentController extends Controller
{
    private const KEY = 'homepage';

    public function edit(): View
    {
        $content = FarnamSiteContent::query()->firstOrCreate(
            ['key' => self::KEY],
            ['content' => self::defaultContent()]
        );

        return view('farnam-admin.edit', ['content' => array_merge(self::defaultContent(), $content->content ?? [])]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_eyebrow' => ['required', 'string', 'max:160'],
            'hero_title' => ['required', 'string', 'max:220'],
            'hero_copy' => ['required', 'string', 'max:1200'],
            'services_title' => ['required', 'string', 'max:180'],
            'services_intro' => ['required', 'string', 'max:1200'],
            'about_title' => ['required', 'string', 'max:180'],
            'about_copy' => ['required', 'string', 'max:1800'],
            'contact_title' => ['required', 'string', 'max:180'],
            'contact_copy' => ['required', 'string', 'max:1200'],
            'phone' => ['required', 'string', 'max:24', 'regex:/^[0-9+()\-\s]+$/'],
            'services' => ['required', 'array', 'min:1', 'max:12'],
            'services.*.title' => ['required', 'string', 'max:120'],
            'services.*.description' => ['required', 'string', 'max:500'],
        ]);

        FarnamSiteContent::query()->updateOrCreate(
            ['key' => self::KEY],
            ['content' => $validated]
        );

        return redirect()->route('farnam.admin.edit')->with('status', 'تغییرات وب‌سایت فرنام ذخیره شد.');
    }

    public static function defaultContent(): array
    {
        return [
            'hero_eyebrow' => 'همراه مطمئن تصمیم‌های مالی کسب‌وکار',
            'hero_title' => 'تصمیم‌های مالی بهتر، کسب‌وکار قدرتمندتر',
            'hero_copy' => 'فرنام در حسابداری، مدیریت مالی، بهای تمام‌شده و امور مالیاتی در کنار کسب‌وکار شماست.',
            'services_title' => 'خدمات مالی، متناسب با نیاز کسب‌وکار شما',
            'services_intro' => 'از ساماندهی اطلاعات حسابداری تا تحلیل مالی و مشاوره مالیاتی؛ خدمات فرنام بر شناخت مسئله و ارائه راهکار متناسب با شرایط هر مجموعه متمرکز است.',
            'about_title' => 'فرنام؛ همراه شما برای مدیریت آگاهانه امور مالی',
            'about_copy' => 'هر کسب‌وکار، شرایط و نیازهای مالی ویژه خود را دارد. رویکرد فرنام بر شناخت دقیق این نیازها، نظم‌بخشی به اطلاعات، تحلیل مسئله و ارائه راهکارهای کاربردی در حوزه حسابداری، مالی و مالیاتی استوار است. هدف، فراهم‌کردن دیدی روشن‌تر برای مدیران و کمک به تصمیم‌گیری مسئولانه‌تر است.',
            'contact_title' => 'برای شروع یک گفت‌وگوی حرفه‌ای آماده‌ایم.',
            'contact_copy' => 'برای دریافت مشاوره در زمینه حسابداری، امور مالی و مالیاتی با فرنام تماس بگیرید. کارشناسان ما آماده شنیدن نیازهای کسب‌وکار شما و گفت‌وگو درباره مسیر مناسب همکاری هستند.',
            'phone' => '09130162404',
            'services' => [
                ['title' => 'خدمات حسابداری', 'description' => 'ساماندهی فرآیندهای حسابداری، ثبت و طبقه‌بندی اطلاعات و بهبود کیفیت گزارش‌های مالی.'],
                ['title' => 'مشاوره مالی', 'description' => 'تحلیل وضعیت مالی، بررسی هزینه‌ها و پشتیبانی اطلاعاتی برای تصمیم‌های مدیریتی.'],
                ['title' => 'مشاوره مالیاتی', 'description' => 'بررسی تکالیف و پرونده‌های مالیاتی، شناخت ریسک‌ها و همراهی در چارچوب قوانین و مقررات.'],
                ['title' => 'حسابداری صنعتی و بهای تمام‌شده', 'description' => 'تحلیل مواد، دستمزد و سربار، هزینه‌یابی و شناخت عوامل مؤثر بر بهای تمام‌شده محصولات.'],
                ['title' => 'گزارشگری مدیریتی', 'description' => 'طراحی و تحلیل گزارش‌هایی که تصویر روشن‌تری از عملکرد و وضعیت مالی مجموعه ارائه می‌کنند.'],
                ['title' => 'استقرار و بهبود سیستم مالی', 'description' => 'بررسی ساختارها و گردش کار مالی و پیشنهاد مسیرهای بهبود متناسب با اندازه و فعالیت شرکت.'],
            ],
        ];
    }
}
