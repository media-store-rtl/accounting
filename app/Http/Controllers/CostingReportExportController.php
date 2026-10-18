<?php

namespace App\Http\Controllers;

use App\Services\CostingReportService;
use App\Services\InventoryValuationService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

final class CostingReportExportController extends Controller
{
    public function export(Request $request, CostingReportService $service, InventoryValuationService $valuationService, string $format)
    {
        abort_unless(in_array($format, ['summary', 'full'], true), 404);
        $companyId = CompanyAuthorization::authorize($request, 'costing.report.view');
        $data = $request->validate([
            'fiscal_year_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'goods_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'personnel_id' => 'nullable|integer',
            'production_section_id' => 'nullable|integer',
            'production_stage_id' => 'nullable|integer',
            'valuation_method' => 'nullable|in:fifo,weighted_average',
        ]);
        $personnelId = $data['personnel_id'] ?? null;
        $sectionId = $data['production_section_id'] ?? null;
        $stageId = $data['production_stage_id'] ?? null;
        $valuationMethod = $data['valuation_method'] ?? $valuationService->methodForCompany($companyId);

        if ($personnelId !== null) {
            abort_unless(DB::table('personnel')->where('id', $personnelId)->where('account_id', $request->user()->account_id)->exists(), 422, 'کارگر انتخاب‌شده معتبر نیست.');
        }
        if ($sectionId !== null) {
            abort_unless(DB::table('production_sections as ps')->join('locations as l', 'l.id', '=', 'ps.location_id')->where('ps.id', $sectionId)->where('l.company_id', $companyId)->exists(), 422, 'قسمت تولید انتخاب‌شده معتبر نیست.');
        }
        if ($stageId !== null) {
            abort_unless(DB::table('production_stages as st')->join('production_routes as pr', 'pr.id', '=', 'st.production_route_id')->where('st.id', $stageId)->where('pr.company_id', $companyId)->exists(), 422, 'مرحله تولید انتخاب‌شده معتبر نیست.');
        }

        $report = $service->generate(
            $companyId,
            $data['fiscal_year_id'] ?? null,
            $data['order_id'] ?? null,
            $data['goods_id'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $personnelId,
            $sectionId,
            $stageId,
            $valuationMethod
        );

        $book = new Spreadsheet();
        $summary = $book->getActiveSheet();
        $summary->setTitle('خلاصه');
        $summary->fromArray([
            ['گزارش بهای تمام‌شده'],
            ['روش ارزش‌گذاری', $valuationMethod === 'fifo' ? 'FIFO' : 'میانگین موزون'],
            ['از تاریخ', $data['start_date'] ?? '—', 'تا تاریخ', $data['end_date'] ?? '—'],
            ['شاخص', 'مبلغ / مقدار'],
            ['جمع مواد ارزش‌گذاری‌شده (ریال)', $report['totals']['material_cost'] ?? 0],
            ['جمع دستمزد (ریال)', $report['totals']['labor_cost'] ?? 0],
            ['جمع ضایعات محصول (ریال)', $report['totals']['scrap_cost'] ?? 0],
            ['سایر هزینه‌های مستقیم (ریال)', $report['totals']['direct_cost'] ?? 0],
            ['جمع کل هزینه‌ها (ریال)', $report['totals']['total_cost'] ?? 0],
            ['مقدار مواد بدون ارزش‌گذاری', $report['totals']['unvalued_material_quantity'] ?? 0],
            ['مصرف گزارش‌های تولیدِ نیازمند تطبیق', $report['totals']['production_report_unvalued_material_quantity'] ?? 0],
            [],
            ['محصول', 'کد محصول', 'مقدار تولید', 'مواد (ریال)', 'دستمزد (ریال)', 'ضایعات (ریال)', 'هزینه مستقیم (ریال)', 'جمع هزینه (ریال)', 'بهای هر واحد (ریال)', 'مقدار بدون ارزش‌گذاری', 'وضعیت'],
        ], null, 'A1');

        $row = 14;
        foreach (($report['product_rows'] ?? []) as $item) {
            $incomplete = (float) ($item['unvalued_material_quantity'] ?? 0) > 0;
            $summary->fromArray([[
                $item['goods_name'] ?? '—',
                $item['goods_code'] ?? '—',
                $item['produced_quantity'] ?? 0,
                $item['material_cost'] ?? 0,
                $item['labor_cost'] ?? 0,
                $item['scrap_cost'] ?? 0,
                $item['direct_cost'] ?? 0,
                $item['total_cost'] ?? 0,
                $item['unit_cost'] ?? null,
                $item['unvalued_material_quantity'] ?? 0,
                $incomplete ? 'ناقص؛ نیازمند ارزش‌گذاری/تطبیق' : 'بر اساس داده‌های ثبت‌شده',
            ]], null, 'A' . $row++);
        }
        $this->styleSheet($summary, 'A4:K4', 'A13:K13', 'A1:K1');
        $summary->freezePane('A14');
        if ($row > 14) $summary->setAutoFilter('A13:K' . ($row - 1));
        $summary->getColumnDimension('A')->setWidth(34);
        $summary->getColumnDimension('K')->setWidth(30);
        foreach (range('B', 'J') as $column) $summary->getColumnDimension($column)->setAutoSize(true);
        $summary->getStyle('B5:J11')->getNumberFormat()->setFormatCode('#,##0.####');

        if ($format === 'full') {
            $production = $book->createSheet();
            $production->setTitle('گزارش‌های تولید');
            $production->fromArray([[
                'شماره گزارش', 'تاریخ تولید', 'محصول', 'کد محصول', 'کارگر', 'بخش تولید', 'مرحله تولید',
                'مقدار تولید', 'مقدار تحویل‌شده', 'ضایعات محصول', 'مواد ارزش‌گذاری‌شده (ریال)',
                'دستمزد (ریال)', 'جمع قابل انتساب (ریال)', 'مقدار بدون ارزش', 'وضعیت ارزش‌گذاری',
            ]], null, 'A1');
            $r = 2;
            foreach (($report['production_reports'] ?? []) as $item) {
                $materials = collect($item['materials'] ?? []);
                $materialCost = (float) $materials->sum('material_cost');
                $unvalued = (float) $materials->sum('unvalued_quantity');
                $production->fromArray([[
                    $item['report_id'] ?? null,
                    $item['work_date'] ?? null,
                    $item['goods_name'] ?? '—',
                    $item['goods_code'] ?? '—',
                    $item['worker_name'] ?? '—',
                    $item['section_name'] ?? '—',
                    implode('، ', $item['stage_names'] ?? []),
                    $item['produced_quantity'] ?? 0,
                    $item['received_quantity'] ?? 0,
                    $item['scrap_quantity'] ?? 0,
                    $materialCost,
                    $item['labor_cost'] ?? 0,
                    $materialCost + (float) ($item['labor_cost'] ?? 0),
                    $unvalued,
                    $unvalued > 0 ? 'ناقص؛ نیازمند تطبیق/ارزش‌گذاری' : 'کامل بر اساس ریز مواد',
                ]], null, 'A' . $r++);
            }
            $this->styleSheet($production, 'A1:O1', null, null);
            $production->freezePane('A2');
            if ($r > 2) $production->setAutoFilter('A1:O' . ($r - 1));
            foreach (range('A', 'O') as $column) $production->getColumnDimension($column)->setAutoSize(true);

            $materialsSheet = $book->createSheet();
            $materialsSheet->setTitle('ریز مواد مصرفی');
            $materialsSheet->fromArray([[
                'شماره گزارش', 'تاریخ تولید', 'محصول نهایی', 'کد ماده', 'نام ماده',
                'مقدار مصرف', 'ضایعات ماده', 'واحد', 'مقدار ارزش‌گذاری‌شده',
                'نرخ واحد (ریال)', 'مبلغ ارزش‌گذاری‌شده (ریال)', 'مقدار بدون ارزش', 'وضعیت',
            ]], null, 'A1');
            $r = 2;
            foreach (($report['production_reports'] ?? []) as $item) {
                foreach (($item['materials'] ?? []) as $material) {
                    $materialsSheet->fromArray([[
                        $item['report_id'] ?? null,
                        $item['work_date'] ?? null,
                        $item['goods_name'] ?? '—',
                        $material['material_code'] ?? '—',
                        $material['material_name'] ?? '—',
                        $material['quantity'] ?? 0,
                        $material['scrap_quantity'] ?? 0,
                        $material['unit_symbol'] ?? $material['unit_name'] ?? '—',
                        $material['valued_quantity'] ?? 0,
                        $material['unit_cost'] ?? null,
                        $material['material_cost'] ?? 0,
                        $material['unvalued_quantity'] ?? 0,
                        !empty($material['cost_complete']) ? 'کامل' : 'ناقص؛ نیازمند تطبیق/ارزش‌گذاری',
                    ]], null, 'A' . $r++);
                }
            }
            $this->styleSheet($materialsSheet, 'A1:M1', null, null);
            $materialsSheet->freezePane('A2');
            if ($r > 2) $materialsSheet->setAutoFilter('A1:M' . ($r - 1));
            foreach (range('A', 'M') as $column) $materialsSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $book->setActiveSheetIndex(0);
        $filename = 'costing-' . $format . '-' . now()->format('Ymd-His') . '.xlsx';
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function styleSheet($sheet, ?string $headerRange, ?string $tableHeaderRange, ?string $titleRange): void
    {
        foreach ([$headerRange, $tableHeaderRange] as $range) {
            if ($range) {
                $sheet->getStyle($range)->getFont()->setBold(true);
                $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF173B52');
                $sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
        if ($titleRange) {
            $sheet->mergeCells($titleRange);
            $sheet->getStyle($titleRange)->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle($titleRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getSheetView()->setRightToLeft(true);
        $sheet->getDefaultColumnDimension()->setWidth(16);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
    }
}
