# Accounting Project Context

## Repository
- GitHub: `media-store-rtl/accounting`
- Default branch: `main`
- Production repository checkout: `/home/mediast1/repositories/accounting`
- Production application: `/home/mediast1/accounting`

## Project
Laravel-based independent accounting system for the Media Store RTL / My Medimo ecosystem.

## Current SSO integration
Accounting integrates with Web2022 through an SSO flow:
1. Accounting sends the user to Web2022: `/accounting/sso/start`.
2. Web2022 returns an SSO token to Accounting's callback.
3. Accounting exchanges the token with Web2022 at `/accounting/sso/exchange`.
4. Accounting creates or finds the local user, links `web2022_user_id` and `web2022_subscription_id`, logs the user in, regenerates the session, and redirects to the dashboard.

Current GitHub `main` implementation of `AccountingSsoController` uses `services.web2022.url` and `services.web2022.sso_secret`.

## Deployment
- Production deployment is monitored from cPanel and is triggered when `main` changes.
- `.cpanel.yml` deploys to `/home/mediast1/accounting/`, installs PHP/Node dependencies, builds assets, prepares storage, and runs `php artisan optimize:clear`.
- Do not assume a GitHub commit is already deployed; verify the deployed checkout/commit before diagnosing production behavior.

## Subscription Access work
The current feature work is to introduce subscription-aware access for Accounting users, with these planned areas:
1. Migration — `accounting_subscriptions` table
2. Model — `AccountingSubscription`
3. SSO — subscription snapshot/data synchronization
4. Access Control — subscription-based authorization/checks
5. Dashboard UI — subscription status
6. Production Deploy & Test — merge, deploy, migrate, and integration-test

Known feature-branch migration mentioned during the work:
`2026_10_05_000001_create_accounting_subscriptions_table.php`.

## Production debugging facts already established
- Server repo branch was `main`.
- At the time of investigation, server `main` was at short SHA `3b8793d`, while GitHub `origin/main` was at `db7e2f4`.
- The deployed `AuthController.php` matched the server repository version exactly.
- The new subscription migration was absent from the deployed application because it was on the subscription feature branch, not deployed `main`.
- Accounting session configuration inspected on the server: driver `file`, cookie `accounting_session`, domain `null`.
- The server and inspected Accounting/Web2022 SSO secret config values were found to resolve to the same underlying value at that time.

## Team operating rules
- Every new ChatGPT/project chat is treated as a specialist working on one assigned task.
- Before making changes, read this file and `TASK_STATUS.md`.
- Work only within the assigned task unless a dependency or blocker requires coordination.
- Never assume production state from GitHub state; verify both when relevant.
- Record meaningful discoveries, decisions, blockers, and completed work in `TASK_STATUS.md`.
- Prefer small, reviewable commits/PRs.
