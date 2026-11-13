# Dolibarr integration boundary and access architecture

## Product boundary (non-negotiable)

Laravel Accounting is not a second accounting application for Dolibarr and is not a business-logic gateway.

Laravel's only Dolibarr-specific responsibility is to decide whether the currently authenticated user, acting within the currently selected company, is entitled to enter Dolibarr. Laravel must not implement, proxy, duplicate, or synchronize Dolibarr's accounting workflows.

All Dolibarr business capabilities remain inside Dolibarr, including its users and roles, ledgers, journals, invoices, payments, taxes, inventory, reports, configuration, and audit trail. Dolibarr's own database is authoritative for Dolibarr data. Laravel's database remains authoritative for Laravel data and the subscription entitlement it receives from the subscription source.

## Authorization decision

The launch decision is scoped to the active Laravel user and selected company. It must fail closed unless all of the following are true:

1. The Laravel user is authenticated and active.
2. The selected company is active and is an active membership of that user.
3. The company's subscription entitlement explicitly includes the Dolibarr product/feature (contract code: `dolibarr.access`).
4. The entitlement status is active, its start time has passed, and it has not expired.
5. The entitlement is sufficiently fresh according to the subscription verification policy.

An active generic subscription alone must not grant Dolibarr access. A plan without the explicit `dolibarr.access` entitlement must be denied. A missing, pending, not-yet-started, or stale/unverifiable entitlement must be denied. **Ordinary expiry is a distinct state:** it changes the customer's existing Dolibarr environment to read-only rather than deleting data or making historical records inaccessible. The user may still launch a restricted read-only session to view, search, print, and download existing records and reports. All data-changing operations must be rejected server-side across the UI, direct requests, APIs, bulk actions, imports/uploads, and background operations. Hiding UI controls alone is insufficient. Renewal restores normal permitted operation in the same Dolibarr environment and database; it does not create a new database or fiscal year. Suspension or explicit revocation for security, fraud, or legal reasons may instead block access entirely under a separately defined policy.

This is a product entitlement check, not a copy of Dolibarr's internal role/permission system. Once inside Dolibarr, Dolibarr itself decides what the mapped Dolibarr user can do. Laravel must not mirror Dolibarr roles or individual module permissions.

## Secure launch boundary

A Laravel-only button or redirect is not sufficient if users can open the Dolibarr host directly. The Dolibarr origin must not be independently reachable around the entitlement gate.

Preferred launch sequence:

1. The user selects a company in Laravel and clicks "Open Dolibarr".
2. A Laravel authenticated route checks the authorization decision above. It does not query or manipulate accounting records.
3. Laravel issues a short-lived, single-use signed launch assertion (or opaque one-time code) containing only the minimum identity and company context required for the handoff, with an explicit audience, expiry, nonce, and unique identifier.
4. A thin Dolibarr-side authentication adapter validates the signature, audience, expiry, nonce/replay status, and the identity-to-Dolibarr-user mapping, then establishes a normal Dolibarr session.
5. The adapter contains no accounting/business rules and does not call Laravel for ordinary Dolibarr business operations. Dolibarr continues to authorize all in-app actions using its own users, groups, and permissions.

The assertion must be transferred over HTTPS, must not be placed in long-lived URLs or logs, must be single-use, and must not contain passwords or subscription secrets. Signing keys belong in server-side secrets and must be separate from the existing storefront/Accounting SSO secrets. Direct access to the Dolibarr origin must be blocked or routed through an enforcement point that cannot be bypassed. Session lifetime and entitlement revocation latency must be explicitly designed and tested.

If a signed SSO handoff is not desired, the safe alternative is to keep Dolibarr behind a reverse proxy/access gateway that checks a short-lived Laravel-issued authorization assertion; simply redirecting to a public Dolibarr login page does not enforce the subscription gate.

## Separation of responsibilities

| Concern | Owner |
|---|---|
| Subscription plan, payment and product entitlement source | Subscription platform |
| Local entitlement snapshot and launch eligibility | Laravel |
| Laravel login, selected company and active membership | Laravel |
| Dolibarr user provisioning/mapping and session | Dolibarr authentication adapter |
| Dolibarr roles, groups and module permissions | Dolibarr |
| Accounting, inventory, tax, invoices, payments and reports in Dolibarr | Dolibarr |
| Dolibarr database, upgrades, backups and audit history | Dolibarr operations |

The Dolibarr authentication adapter is a narrow trust-boundary component, not a second integration API for accounting functions. Do not synchronize accounting tables between the two applications as part of this access check.

## Existing implementation facts and gaps

- Laravel already has a company-scoped `subscription_entitlements` record and `SubscriptionEntitlement::isActive()`.
- The current model checks `status === active` and expiry, but does not check `starts_at`, an explicit product feature code, or verification freshness. Therefore it is not yet sufficient for the Dolibarr gate.
- The existing `AccountingSsoController` exchanges identity with the storefront/Web2022. It is not a Dolibarr connector and its secrets/protocol must not be reused implicitly.
- The existing user/company membership and company selection can scope a future access decision. Do not infer Dolibarr access from a Laravel role permission or from a generic active subscription unless the product owner explicitly defines that rule.
- The Dolibarr upstream source is pinned separately at `dolibarr/upstream`; it is not installed, configured, exposed, or connected to Laravel by this document.

## Implementation phases and acceptance criteria

### Phase A — entitlement contract
- Agree with the subscription source on the authoritative `dolibarr.access` product entitlement, payload shape, and verification freshness/expiry rules.
- Define whether the entitlement is granted per company/account and how company switching affects it.
- Deny by default for absent or malformed entitlement data.

### Phase B — secure entry and identity handoff
- Add one Laravel entry route and UI link; its only business decision is the entitlement check.
- Define a stable mapping from Laravel identity/company to a Dolibarr user/entity. Do not silently create unrestricted administrator users.
- Implement the minimal Dolibarr authentication adapter and one-time signed assertion, or a non-bypassable access gateway.
- Ensure the Dolibarr origin cannot be reached directly around the gate.

### Phase C — validation
- Test active entitlement, missing feature, pending/suspended/expired/not-yet-started entitlement, stale verification, inactive user, inactive membership, inactive company, and company switching.
- Test invalid signature, wrong audience, expired/replayed assertion, unmapped identity, and direct-origin bypass.
- Confirm that Laravel has no Dolibarr accounting CRUD endpoints and that Dolibarr business permissions remain exclusively in Dolibarr.
- Test session expiry and entitlement revocation behavior.

### Out of scope
- Rebuilding Dolibarr accounting features in Laravel.
- Synchronizing Dolibarr ledgers, invoices, payments, inventory or permissions into Laravel.
- Automatically provisioning a super-admin or copying Laravel roles into Dolibarr.
- Installing or deploying Dolibarr to production without a separate reviewed and approved deployment plan.


## Expired subscription: read-only access and renewal

The subscription period and the customer's fiscal year are independent. Buying or renewing a plan must not create a fiscal year or a replacement Dolibarr database. Each customer retains the same Dolibarr environment across renewals.

- While the `dolibarr.access` entitlement is active, the user may perform operations allowed by that user's native Dolibarr roles and permissions.
- When the entitlement expires normally, the customer may still enter the existing Dolibarr environment in **read-only mode**. Viewing, searching, printing, and downloading existing documents and reports remain available.
- In read-only mode, all mutations are forbidden: create, edit, delete, validate, post, submit, pay, stock movement, import, upload, configuration changes, and user/role administration. Enforcement must be server-side and cover forms, direct HTTP requests, APIs, bulk actions, and background jobs. UI button hiding is not a security boundary.
- Laravel remains responsible only for determining the entitlement state and conveying the allowed access mode through the secure handoff. Dolibarr or a non-bypassable enforcement layer must enforce read-only behavior for every request, including attempts made through an existing session or direct origin access.
- Renewing the entitlement restores the same environment to normal operation subject to the user's Dolibarr permissions. It does not create a new database, user space, or fiscal year.
- Normal expiry must never delete or reset the customer's database, documents, uploaded files, or financial history. Explicit suspension/revocation for security, fraud, or legal reasons may block access entirely and is distinct from ordinary expiry.

This policy is a required acceptance criterion. Tests must prove that read and export operations remain available after expiry and that every write path is rejected; they must also prove that renewal restores permitted writes without replacing the database or historical data.
