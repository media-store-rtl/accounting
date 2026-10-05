# Accounting Task Status

Last updated: 2026-10-05

## Active work
Subscription Access + SSO integration.

## Task board

| ID | Task | Status | Notes |
|---|---|---|---|
| 1 | Migration — `accounting_subscriptions` | Not started / verify feature branch | Migration `2026_10_05_000001_create_accounting_subscriptions_table.php` was previously identified on the subscription feature branch. |
| 2 | Model — `AccountingSubscription` | Not started | Depends on final migration/schema. |
| 3 | SSO — subscription snapshot/data | In progress / inspect | Current `main` SSO controller already reads `subscription_id` from the Web2022 exchange payload and stores it on the User as `web2022_subscription_id`. |
| 4 | Access Control — subscription authorization | Not started | Define exact active/expired/cancelled rules before implementation. |
| 5 | Dashboard UI — subscription status | Not started | Should consume the finalized access/subscription model. |
| 6 | Production Deploy & Test | Blocked until feature is reviewed/merged | Production deployment follows `main`; migration must be present on deployed `main` before running it in production. |

## Important repository/deployment state
- Repository: `media-store-rtl/accounting`
- Production checkout: `/home/mediast1/repositories/accounting`
- Production app: `/home/mediast1/accounting`
- Production deployment is tied to `main` changes via cPanel.

## How a new specialist chat should start
Read `PROJECT_CONTEXT.md` and this file first. Then work only on the task assigned to that specialist. Before changing shared architecture, check dependencies and record the decision here.

## Change log
- 2026-10-05: Created shared project context and task board on a dedicated setup branch. No production deployment has been requested or triggered by this setup branch.
