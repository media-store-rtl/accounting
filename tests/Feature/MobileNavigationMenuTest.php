<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileNavigationMenuTest extends TestCase
{
    public function test_mobile_navigation_exposes_desktop_sections_and_management_links(): void
    {
        $view = file_get_contents(base_path('resources/views/layouts/dashboard-shell.blade.php'));

        $this->assertNotFalse($view);
        $this->assertStringContainsString('>خانه</a>', $view);
        $this->assertStringContainsString('>فروش</a>', $view);
        $this->assertStringContainsString('>تأمین</a>', $view);
        $this->assertStringContainsString('>انبار</a>', $view);
        $this->assertStringContainsString('>تولید</a>', $view);
        $this->assertStringContainsString('>گزارشات</a>', $view);
        $this->assertStringContainsString('>بیشتر</a>', $view);

        foreach ([
            'درخواست‌های خرید',
            'خریدها',
            'رسیدهای خرید',
            'تحویل مواد',
            'اعلان‌ها',
            'تنظیم گیرندگان اعلان',
            'پشتیبان‌گیری',
            'اطلاعات مجموعه',
            'سال‌های مالی',
            'پرسنل',
            'کاربران',
            'نقش‌ها و دسترسی‌ها',
            'مشتریان',
            'کالاها و کد کالا',
            'دسته‌بندی کالا',
            'واحدهای اندازه‌گیری',
            'تأمین‌کنندگان',
            'قسمت‌های تولید',
            'مراحل تولید',
            'مسیرهای تولید',
            'کارکرد تولید',
        ] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('id="mobile-supply-panel"', $view);
        $this->assertStringContainsString('id="mobile-warehouse-panel"', $view);
        $this->assertStringContainsString('id="mobile-more-panel"', $view);
        $this->assertStringContainsString('id="mobile-home-panel"', $view);
        $this->assertStringContainsString('id="mobile-reports-panel"', $view);
        $this->assertStringContainsString('id="mobile-production-panel"', $view);
        $this->assertStringContainsString("\$can['production_labor'] ?? false", $view);
        $this->assertStringContainsString("route('workflows.labor')", $view);
        $this->assertStringContainsString("request()->routeIs('workflows.labor*')", $view);
        $productionPanelStart = strpos($view, '<div class="mobile-sales-panel" id="mobile-production-panel"');
        $reportsPanelStart = strpos($view, '<div class="mobile-sales-panel" id="mobile-reports-panel"', $productionPanelStart === false ? 0 : $productionPanelStart);
        $this->assertNotFalse($productionPanelStart);
        $this->assertNotFalse($reportsPanelStart);
        $productionPanel = substr($view, $productionPanelStart, $reportsPanelStart - $productionPanelStart);
        $this->assertStringContainsString("route('workflows.labor')", $productionPanel);
        $this->assertStringContainsString('کارکرد تولید', $productionPanel);

        $supplyPanelStart = strpos($view, '<div class="mobile-sales-panel" id="mobile-supply-panel"');
        $warehousePanelStart = strpos($view, '<div class="mobile-sales-panel" id="mobile-warehouse-panel"', $supplyPanelStart === false ? 0 : $supplyPanelStart);
        $productionPanelStartForWarehouse = strpos($view, '<div class="mobile-sales-panel" id="mobile-production-panel"', $warehousePanelStart === false ? 0 : $warehousePanelStart);
        $this->assertNotFalse($supplyPanelStart);
        $this->assertNotFalse($warehousePanelStart);
        $this->assertNotFalse($productionPanelStartForWarehouse);
        $supplyPanel = substr($view, $supplyPanelStart, $warehousePanelStart - $supplyPanelStart);
        $warehousePanel = substr($view, $warehousePanelStart, $productionPanelStartForWarehouse - $warehousePanelStart);
        $this->assertStringNotContainsString('موجودی انبار', $supplyPanel);
        $this->assertStringNotContainsString('تحویل و ارسال', $supplyPanel);
        $this->assertStringContainsString('موجودی انبار', $warehousePanel);
        $this->assertStringContainsString('تحویل و ارسال', $warehousePanel);
        $this->assertStringContainsString('درخواست‌های مواد برای بررسی', $warehousePanel);
        $this->assertStringContainsString('انبارها و محل‌ها', $warehousePanel);

    }
}
