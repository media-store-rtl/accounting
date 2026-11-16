<?php

namespace Tests\Feature;

use Tests\TestCase;

class FarnamPublicPagesTest extends TestCase
{
    public function test_service_pages_article_library_and_case_studies_are_available(): void
    {
        $this->get('/services/accounting-management')->assertOk()->assertSee('حسابداری و مدیریت مالی شرکت‌ها');
        $this->get('/services/industrial-costing')->assertOk()->assertSee('حسابداری صنعتی و بهای تمام‌شده');
        $this->get('/services/tax-services')->assertOk()->assertSee('خدمات مالیاتی');
        $this->get('/services/financial-advisory')->assertOk()->assertSee('مشاوره مالی و مدیریتی');
        $this->get('/articles')->assertOk()->assertSee('دانش مالی برای تصمیم‌های بهتر');
        $this->get('/articles/accounting-profit-vs-cash')->assertOk()->assertSee('سود حسابداری با موجودی نقد شرکت متفاوت است');
        $this->get('/case-studies')->assertOk()->assertSee('نمونه‌کار قابل انتشار در حال آماده‌سازی است');
    }

    public function test_unknown_public_content_slug_returns_not_found(): void
    {
        $this->get('/services/not-a-service')->assertNotFound();
        $this->get('/articles/not-an-article')->assertNotFound();
    }
}
