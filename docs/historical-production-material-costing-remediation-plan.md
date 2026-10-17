# Historical production material costing remediation plan

**Status:** Prepared for review; investigation only.
**Scope:** Missing material cost for production shift reports dated 2026-10-12 through 2026-10-15.
**Safety:** This plan does not change production data and must not be treated as authorization to backfill stock movements.

## Evidence collected

The production database was inspected read-only by the operator through phpMyAdmin:

- production_shift_report_materials contains material lines for goods_id = 1; the affected report lines total 32 units. One rejected report records 1 unit of scrap and must not be treated as an approved material consumption without checking the report status.
- For inventory_movements, no rows for goods_id = 1 were returned for occurred_at >= 2026-10-12 00:00:00 and occurred_at < 2026-10-17 00:00:00.
- For inventory_consumption_costs, no rows for goods_id = 1 were returned for consumed_at >= 2026-10-12 00:00:00 and consumed_at < 2026-10-17 00:00:00.
- No matching material_handovers / material_handover_items rows were returned for the same item and date range.
- An all-date handover query found two completed handovers for goods_id = 1, each quantity 1, both on 2026-10-17 (handover IDs 1 and 2; section 1; warehouse location 2).
- Six cost-layer rows for goods_id = 1 were returned, all received on 2026-10-17. Their displayed quantities sum to 8 and their total_cost values sum to 86,000. These rows are later than the affected reports and cannot be assumed to price earlier consumption.
- Reports 8-12 and 14 were shown as received, report 13 as approved but not received; report 7 and report 1 were rejected. The report lines should be revalidated against live data and status before any correction.
- The current ProductionShiftReportMaterialConsumptionService consumes report materials when the review/approve request runs. It does not itself replay old reports already approved/received before that flow existed.
- CostingReportService calculates material cost from valued inventory consumption records, not from report material lines alone. InventoryValuationService intentionally excludes cost layers received after the effective movement time.


## Report-level reconciliation baseline

The read-only audit output for company 1 and work dates 2026-10-12 through 2026-10-16 reported 14 reports checked, 13 report/material groups, 44 units across all report material lines, zero linked consumption movement quantity, zero valued quantity, and zero linked valuation cost. The report/material rows had no matching `production_consumption` movements or valuation rows.

The reported 32-unit figure is specifically the sum of the received reports below; it is not the sum of every material line in the date range:

| Report ID | Work date | Status | Material goods ID | Reported quantity |
|---:|---|---|---:|---:|
| 2 | 2026-10-13 | received | 1 | 5 |
| 3 | 2026-10-13 | received | 1 | 1 |
| 4 | 2026-10-13 | received | 1 | 10 |
| 5 | 2026-10-14 | received | 1 | 10 |
| 8 | 2026-10-14 | received | 1 | 1 |
| 9 | 2026-10-14 | received | 1 | 1 |
| 10 | 2026-10-14 | received | 1 | 1 |
| 11 | 2026-10-14 | received | 1 | 1 |
| 12 | 2026-10-14 | received | 1 | 1 |
| 14 | 2026-10-15 | received | 1 | 1 |
| **Total** | | | | **32** |

Reports 1 (rejected, 10 units) and 7 (rejected, 1 unit) are excluded from the 32-unit received-report total. Report 13 is approved but not received, records 1 unit of material goods ID 1, and has produced goods ID 4; keep it as a separate unresolved case rather than adding it to the received-report total. The aggregate of 44 units includes report rows outside the 32-unit received subset, so the two totals must not be conflated.

These are a snapshot of previously supplied audit results, not a substitute for revalidating the current database before any proposed correction.


## Reconciliation worksheet — do not post inventory changes from this template

Complete one row per report/material line using source documents and the live ledger. Leave fields blank or mark `unverified` when evidence is unavailable. Do not estimate a unit cost from later receipts.

| Report | Qty | Receipt/purchase layer evidence (ID/date/qty) | Transfer into production section (movement/handover ID and date) | Physical consumption evidence | Historical layer quantity available at report time | Supported unit cost and source | Reviewer / decision |
|---:|---:|---|---|---|---|---|---|
| 2 | 5 | unverified | unverified | unverified | unverified | unresolved | pending |
| 3 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 4 | 10 | unverified | unverified | unverified | unverified | unresolved | pending |
| 5 | 10 | unverified | unverified | unverified | unverified | unresolved | pending |
| 8 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 9 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 10 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 11 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 12 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| 14 | 1 | unverified | unverified | unverified | unverified | unresolved | pending |
| **Received-report subtotal** | **32** | | | | | | |
| 13 (separate case; approved, not received) | 1 | unverified | unverified | unverified | unverified | unresolved | review separately |

Before any write-mode repair is considered, reconcile each source receipt and transfer against the movement ledger and the production-section location at the effective time. A finished-goods receipt is not evidence of raw-material consumption. If the historical source layer or stock balance cannot be proven, leave the case unresolved and do not backfill a guessed cost.

## Root cause assessment

The immediate reason the costing report has zero material cost is that the report-material rows are not accompanied by matching historical inventory consumption movements and valued consumption records in the queried date range. The available evidence does not establish where the reported 32 units physically came from, whether they were transferred to the production section, or the defensible historical unit cost.

This is therefore a stock-ledger and historical-valuation reconciliation problem, not merely a report-rendering defect.

## Non-negotiable safety rules

1. Do not insert synthetic inventory_movements or inventory_consumption_costs solely from report quantities.
2. Do not backdate a receipt or use a 2026-10-17 cost layer to price a 2026-10-12–15 consumption without accounting approval and a full valuation replay.
3. Do not assume a finished-goods warehouse receipt proves raw materials were issued or consumed.
4. Do not use rejected reports as approved consumption.
5. Do not blindly call consumeApprovedReport() for old reports: it consumes against current production-section stock and could fail or alter current balances rather than reconstruct historical stock.
6. Any eventual write operation must be idempotent, company-scoped, transactional, have a dry-run mode, emit a before/after audit report, and require explicit confirmation.

## Recommended implementation

### Phase A — read-only audit (first code deliverable)

Add an Artisan command, for example php artisan costing:audit-production-materials, that accepts company, date range, and optional report IDs. It should only read data and emit one row per report/material with:

- report ID, date, status, material goods ID and quantity;
- matching production_consumption movement quantity and IDs using reference_type = production_shift_reports and reference_id = report ID;
- matching inventory-consumption valuation records and total cost;
- transfer/handover movements and production-section location;
- cost layers available at the effective report timestamp (never future layers);
- classification: matched, missing_movement, missing_valuation, quantity_mismatch, no_historical_layer, or rejected_report;
- no writes, no repair side effects.

The command must use the report's company ID and must not infer that all rows with the same goods_id belong to the same company or production section.

### Phase B — reviewed reconciliation worksheet

For each unmatched report, inventory/accounting staff must identify the source receipt/layer, source location, transfer to production, and actual consumption. The worksheet should include the evidence and proposed effect on later movements and cost layers. Rows without traceable evidence remain unresolved/unvalued rather than being assigned a guessed cost.

### Phase C — separate correction command (only after approval)

Do not implement or run a write-mode repair until Phase A results and the accounting-approved worksheet exist. If approved, the repair must:
- refuse rejected reports and already-matched reports;
- verify report status and quantities again under transaction/locks;
- verify historical stock availability and cost-layer provenance at the effective timestamp;
- calculate downstream impact of replaying later consumption before changing layer balances;
- prevent duplicate movements and valuation rows;
- support dry-run by default and require an explicit --apply flag;
- log report IDs, movement IDs, valuation IDs, layer IDs, before/after quantities, and actor;
- run against a verified backup/staging copy first.

## Acceptance tests

- An approved/received report with no movement is flagged without creating records.
- A rejected report is flagged and is never proposed as a consumption candidate.
- A report with an exactly matching movement is not duplicated.
- A partial/mismatched movement is flagged for review; it is not silently topped up by the audit command.
- A future cost layer is never proposed as historical valuation for an earlier report.
- A company cannot see or reconcile another company's reports, movements, handovers, or cost layers.
- Running the audit twice produces the same output and no database writes.
- Dry-run correction (if separately implemented later) produces a proposed plan only.

## Decision

**Current recommendation: implement the read-only audit first, not a historical write/backfill.** The evidence presently available is insufficient to safely reconstruct the 32 units or their historical cost. The existing production database remains unchanged.
