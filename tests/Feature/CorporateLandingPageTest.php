<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorporateLandingPageTest extends TestCase
{
    public function test_guest_homepage_displays_the_farnam_corporate_website(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('خدمات مشاوره حسابداری مالی و مالیاتی فرنام')
            ->assertSee('شفافیت مالی، آغاز')
            ->assertSee('مشاوره مالیاتی')
            ->assertSee('حسابداری صنعتی و بهای تمام‌شده')
            ->assertSee('فرآیند همکاری')
            ->assertSee('id="services"', false)
            ->assertSee('id="about"', false)
            ->assertSee('id="contact"', false)
            ->assertSee('lang="fa" dir="rtl"', false)
            ->assertSee('href="/login"', false)
            ->assertSee('۰۹۱۳۰۱۶۲۴۰۴')
            ->assertSee('href="tel:+989130162404"', false)
            ->assertDontSee('حسابداری صنعتی برای کسب‌وکارهای تولیدی')
            ->assertDontSee('https://mymedimo.ir/');
    }
}
