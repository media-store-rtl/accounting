<?php

namespace App\Contracts;

interface ProductionShiftReportMaterialConsumption
{
    public function consumeApprovedReport(int $companyId, int $reportId, int $reviewerId): void;
}
