<?php

namespace App\Console\Commands;

use App\Services\HistoricalProductionConsumptionReconciliationService;
use Illuminate\Console\Command;
use RuntimeException;

final class BackfillHistoricalProductionConsumption extends Command
{
    protected $signature = 'costing:backfill-production-consumption
        {--company= : Required company ID}
        {--report= : One approved/received shift report ID}
        {--actor= : User ID responsible for this explicitly approved reconciliation}
        {--apply : Write missing consumption only after all safety checks pass}';

    protected $description = 'Explicitly reconcile one historical shift report; dry-run by default and refuses ambiguous stock history';

    public function handle(HistoricalProductionConsumptionReconciliationService $reconciliation): int
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        $reportId = filter_var($this->option('report'), FILTER_VALIDATE_INT);
        $actorId = filter_var($this->option('actor'), FILTER_VALIDATE_INT);

        if (!$companyId || $companyId < 1 || !$reportId || $reportId < 1) {
            $this->error('Pass valid --company=ID and --report=ID. No changes were made.');
            return self::FAILURE;
        }

        if (!$this->option('apply')) {
            $this->warn('DRY RUN ONLY: no records were changed.');
        } elseif (!$actorId || $actorId < 1) {
            $this->error('Applying requires --actor=USER_ID for audit attribution. No changes were made.');
            return self::FAILURE;
        }

        try {
            $result = $this->option('apply')
                ? $reconciliation->apply((int) $companyId, (int) $reportId, (int) $actorId, 'CLI')
                : $reconciliation->preview((int) $companyId, (int) $reportId);
        } catch (RuntimeException $exception) {
            $this->error('NOT ELIGIBLE: ' . $exception->getMessage());
            return self::FAILURE;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->error('NOT ELIGIBLE: ' . collect($exception->errors())->flatten()->implode(' '));
            return self::FAILURE;
        }

        if (!$result['eligible']) {
            $this->error('NOT ELIGIBLE: ' . $result['reason']);
            return self::FAILURE;
        }

        $this->table(['Material ID', 'Missing quantity', 'Historical stock', 'Current stock', 'Eligible cost layers'], array_map(
            fn ($material) => [
                $material['goods_id'],
                $this->format((float) $material['quantity']),
                $this->format((float) $material['historical_stock']),
                $this->format((float) $material['current_stock']),
                $this->format((float) $material['eligible_layers']),
            ],
            $result['materials']
        ));

        if ($this->option('apply')) {
            $this->info('Historical consumption was recorded with report linkage and an audit-trail entry.');
        } else {
            $this->info('Eligible in dry-run. No records were changed. Use --apply --actor=USER_ID only after independent review.');
        }

        return self::SUCCESS;
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
