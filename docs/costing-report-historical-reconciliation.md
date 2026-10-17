# Historical costing report reconciliation

**Review date:** 2026-10-17  
**Scope:** Read-only investigation of the missing raw-material cost in the costing report.  
**Production database:** No writes performed.

## Findings from the read-only inspection

- Production shift reports in the affected period (2026-10-12 through 2026-10-15) were present with status `received`.
- The affected report-material rows totalled **32 units** for `goods_id = 1`; the reports also showed **1 unit** of scrap.
- No matching inventory movements were found for `goods_id = 1` in that period.
- No corresponding rows were found in `inventory_consumption_costs` for `goods_id = 1`.
- Cost layers for the item were recorded on 2026-10-17, after the affected report dates.

These findings explain why the costing report could show labour but no valued material consumption. A report-material row by itself is not proof that stock was deducted or that the historical unit cost is known.

## Safe reconciliation rules

1. Do not create a guessed unit cost or backdate a receipt to make the report balance.
2. For each affected report, compare reported material quantities with warehouse-to-production transfers, production-section balances, adjustments, and any existing consumption movements.
3. Where the stock issue and a traceable cost layer can be proven, prepare a proposed correction with report ID, material ID, quantity, date, layer/receipt reference, and expected valuation before any write.
4. Where the quantity or valuation cannot be proven, keep it explicitly unvalued and obtain an accounting/inventory decision.
5. Re-run historical valuation only with a reviewed replay plan: a backdated movement cannot be valued correctly merely by filtering cost layers by receipt date, because later consumptions may already have depleted those layers.
6. The PR change handles future approvals; it does **not** automatically reconstruct historical reports that were already approved/received without consumption movements.

## Verification still required before any historical correction

- Export the exact affected report IDs and material lines.
- Reconcile the 32 reported units against movements and stock balances by production section and date.
- Confirm whether receipts/layers existed in the relevant section at each report's effective date.
- Calculate any replay/revaluation impact on later movements and financial periods.
- Obtain explicit approval for a separate correction plan before changing production data.

**Current decision:** investigation only; no historical stock, movement, or valuation rows should be inserted based solely on the shift-report quantities.
