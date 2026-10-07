# Reports / Reporting Scope Readiness

Status: BLOCKED on Costing implementation.

This document records only the reporting contract required by PROJECT_DEFINITION.md. It does not implement CostCalculation, CostComponent, valuation, or any other Costing functionality.

## 1. Required reports

PROJECT_DEFINITION.md section 11 requires reports to be based on real data recorded across:
Order -> Supply -> Inventory -> Production -> Finished Goods -> Sales.

Minimum required reporting capability:
1. Order Cost Report
2. Product Cost Report

Cost traceability is required within these reports, not a separate invented report.

## 2. Order Cost Report

Show the calculated cost of an order and trace it to real source records.

Required:
- selected order, items and quantities
- calculated cost
- component breakdown: consumed materials/items; purchase-related monetary cost; labor; direct procurement costs such as freight; recorded production scrap
- traceability through order, supply, purchase, inventory, production/operations, labor, scrap, finished-goods output and sale/delivery records when applicable
- no mock/static/manual cost values

Filters:
- active company scope
- fiscal year
- order identifier/number
- date range only if the final Costing/reporting contract explicitly requires it; section 11 does not independently mandate one

## 3. Product Cost Report

Show the calculated cost of a product and trace it to real source records.

Required:
- selected product/goods
- applicable real production/cost records
- quantities and calculated cost
- component breakdown using the same required cost components
- source-record traceability
- no mock/static/manual cost values

Filters:
- active company scope
- fiscal year
- product/goods
- date range only if explicitly required by the final Costing/reporting contract

## 4. Company scope

Every report query must be scoped to the authenticated user's active company. A report must never expose another company's records through IDs, filters, relations, or direct queries.

The repository already models company membership and company-scoped permissions.

## 5. Fiscal-year scope

Reports must support a fiscal year belonging to the active company. Existing operational records such as orders, supply requests and productions carry fiscal_year_id where applicable. Future Costing records must preserve the same company/fiscal-year ownership rules.

## 6. Authorization

Server-side authorization is mandatory; hiding UI options is insufficient.

The implementation must enforce authenticated user, active company membership, reporting permission, and ownership of requested report records. Account-owner behavior must remain consistent with User::hasCompanyPermission().

## 7. Required routes

When unblocked, provide dedicated read-only routes for:
- Order Cost Report index/filter
- Order Cost Report detail/traceability
- Product Cost Report index/filter
- Product Cost Report detail/traceability

Exact URI/name should follow repository conventions. No routes are added while Costing is unavailable.

## 8. Required UI

- report navigation/selection
- required filters
- result table/summary
- cost-component breakdown
- traceability/detail view linking amounts to source records
- empty state for no matching real data
- authorization-aware access

No static sample rows or fabricated totals.

## 9. Data sources / relations

Order: orders, order_items, customers, fiscal_years, companies.

Supply: supply_requests, supply_request_items, orders, productions, users.

Purchasing: purchases, purchase_items, purchase_direct_costs, purchase_receipts, purchase_receipt_items, suppliers.

Inventory: inventory, inventory_movements, locations, goods.

Production: productions, production routes/stages/operations, production stage/operation runs, operation_inputs, operation_outputs, scraps, and personnel/production-section records where applicable.

Costing dependency: PROJECT_DEFINITION.md defines CostCalculation, CostComponent, cost_transactions, overhead_entries and wip_entries. These Costing structures are not present in the current main implementation and are therefore a blocking dependency for actual cost-result reports.

## 10. Test contract

Feature:
- authorized user can open each report
- reports use persisted database records
- no matching data returns an empty result
- order/item and product links are correct

Authorization:
- unauthenticated denied
- inactive company membership denied
- user without reporting permission denied
- cross-company ID access denied
- fiscal year from another company denied

Data correctness:
- component totals equal the Costing result
- materials trace to actual operation inputs/inventory movements
- purchase cost traces to purchase items/direct costs
- labor traces to actual labor records
- scrap traces to recorded scrap
- finished output traces to production operation output
- order/product traceability preserves applicable source links
- company/fiscal-year filters cannot mix or leak records

PASS requires real Costing output plus demonstrable source traceability. Controller/view existence alone is not completion.

## 11. Current decision

Reports are BLOCKED. No safe production implementation independent of Costing can produce the required business output. Adding only routes/controllers/views would create an incomplete facade and violate the real-data requirement.

Only this readiness/contract document is added. No Costing or other Scope is modified.
