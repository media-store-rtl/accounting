# Production release checklist — accounting

**Purpose:** Operational checklist for releasing the approved code on `main`. This document is not authorization to deploy or to modify production data.

## 1. Confirm the release candidate

- [ ] Confirm the intended commit on `main` and review the included pull requests.
- [ ] Confirm the GitHub Actions run for that exact commit completed successfully.
- [ ] Review configuration changes, migrations, scheduled jobs, and middleware changes in the release diff.
- [ ] Confirm the production PHP/runtime and required extensions are compatible with the release.
- [ ] Record the planned release window and the person performing the release.

## 2. Protect production before changing files

- [ ] Take and verify a restorable database backup.
- [ ] Take a file backup appropriate to the deployment method.
- [ ] Preserve `.env`, `storage/`, uploaded files, and user-generated content. Do not replace or delete them as part of a code update.
- [ ] Confirm the rollback target and the steps to restore the prior code and database state.
- [ ] Do not run destructive cleanup commands or change file permissions broadly as a workaround.

## 3. Deploy the reviewed code

- [ ] Update the repository to the explicitly selected, reviewed `main` commit.
- [ ] Follow the host's documented deployment procedure; do not switch branches or deploy an unreviewed HEAD.
- [ ] Install dependencies only as required by the release and the host's deployment setup.
- [ ] Inspect migration status first. Run migrations only when the release contains reviewed migrations and the operator has approved them.
- [ ] Do not run historical stock repair, replay old production reports, insert inventory movements, or create valuation records as part of a code deployment.

## 4. Post-deploy smoke checks

- [ ] Confirm the application responds and authentication/dashboard pages load.
- [ ] Check application logs for new exceptions and failed jobs.
- [ ] Verify a normal production shift report can be reviewed and approved through the intended workflow.
- [ ] Verify a newly approved eligible report consumes materials once, with correct company, production section, work date, movement reference, and valuation links.
- [ ] Verify rejected, already-approved, and out-of-range/future-dated reports do not create unintended consumption.
- [ ] Verify the costing report clearly flags uncertain unit cost where material reconciliation is unresolved.
- [ ] Record the deployed commit, test results, smoke-check outcome, and any rollback decision.

## 5. Historical material-cost discrepancy — separate workstream

The previously documented audit for company 1 and 2026-10-12 through 2026-10-16 found report/material lines without matching consumption movements or linked valuation. In particular, 32 units across received reports dated 2026-10-13 through 2026-10-15 remain unresolved. Later cost layers dated 2026-10-17 are not evidence of historical unit cost for those earlier dates.

- [ ] Revalidate the affected report IDs and statuses against the live ledger.
- [ ] Reconcile each report/material line with source receipts, transfers into the production section, physical-consumption evidence, and historical layer availability.
- [ ] Keep unsupported quantities/costs unresolved; do not infer stock consumption from a finished-goods receipt.
- [ ] If useful, run the existing read-only reconciliation command with a verified company/date scope and retain its output.
- [ ] Require a separately reviewed, accounting-approved correction plan before any historical write or valuation replay.

## Release boundary

This checklist does not report that a backup, deployment, smoke test, or historical correction has occurred. Record each result only after the operator has actually performed and verified it.
