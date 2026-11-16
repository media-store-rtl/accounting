# Dolibarr Accounting

Dolibarr is the target primary product for this online accounting/ERP service. Customer registration, sign-in, user administration, roles, permissions, accounting operations and customer business data are intended to live directly in Dolibarr.

## Repository contents
- `dolibarr/upstream/`: official Dolibarr source, pinned as a Git submodule.
- `dolibarr/custom-modules/`: locally maintained Dolibarr modules.
- `dolibarr/patches/`: documented patches that cannot be implemented through supported extension points.
- `PROJECT_DEFINITION.md`: current product and architecture definition.
- `PROJECT_PROGRESS.md`: chronological project history and verified work status.
- `docs/farnam-corporate-website.md`: brand identity, homepage structure, approved contact details, CMS and lead-management notes.
- `docs/farnam-customer-acquisition-plan.md`: low-budget, contract-focused customer acquisition plan and operating KPIs.
- `docs/dolibarr-cpanel-deployment-preparation.md`: proposed cPanel tenant layout, deployment constraints, and future installation/acceptance checklist.
- `docs/provisioning-service-architecture.md`: approved ownership boundaries and high-level Provisioning architecture within Accounting.
- `docs/accounting-provisioning-module-design.md`: proposed internal module boundaries, API/event contracts, tenant lifecycle, security and implementation gates.

The existing Accounting project is retained. Provisioning is an additional capability implemented within Accounting itself; it is not a replacement project, a separate service, or a reason to retire Accounting. The current Laravel application remains part of the project. Dolibarr is the direct accounting/ERP workspace for each provisioned business, while the coexistence and future division of responsibilities between existing Accounting capabilities and Dolibarr remain subject to explicit, feature-by-feature decisions. This documentation does not authorize removal or retirement of existing Accounting code, data, database, files, or functionality.

## Farnam corporate website

The public guest homepage introduces **Farnam Accounting, Financial and Tax Advisory Services** in Persian and RTL. It uses a premium navy-and-gold visual identity and presents accounting, financial, tax, industrial-costing, management-reporting and financial-system advisory services. The current contact number is **09130162404**, with a direct `tel:+989130162404` link.

The public homepage is a corporate/marketing surface only. It does not replace the Dolibarr ERP direction, the existing Accounting application, the authenticated dashboard, or the Web2022/Accounting Provisioning ownership boundaries. Guests see the corporate homepage at the root route; authenticated users retain the existing dashboard redirect. See [the Farnam website brief](docs/farnam-corporate-website.md) for the design, content, SEO and maintenance rules. The homepage includes a consultation request form and an owner-only lead follow-up panel in the current implementation branch; these changes require review, CI, merge approval and deployment verification before they can be considered live.

## Get the source
```bash
git clone --recurse-submodules --shallow-submodules https://github.com/media-store-rtl/accounting.git
```
For an existing clone:
```bash
git submodule update --init --depth 1 dolibarr/upstream
```
Dolibarr's application document root is `dolibarr/upstream/htdocs`.

## Multi-customer direction
A customer may own multiple independent businesses. Each business has its own commercial subscription and one independent Dolibarr tenant, including a separate database, configuration, documents, sessions and runtime context. Web2022 is the customer-facing UI for creating/managing businesses and owns checkout, payment and commercial subscription state; the existing Accounting project owns canonical customer/business/tenant IDs and tenant lifecycle through its Provisioning capability. Dolibarr owns operational users, roles, permissions and accounting data for each tenant. A controlled, version-pinned release should be deployed into separate tenant application trees unless a shared physical code tree with isolated per-tenant configuration is proven safe under the actual runtime. Dedicated business subdomains are recommended for evaluation, not yet a finalized DNS decision.

## Deployment
The current cPanel deployment file, `.cpanel.yml`, still contains Laravel deployment, Composer/npm build, Laravel migration, and seeder commands. It is not a Dolibarr deployment workflow and must not be treated as one or triggered for Dolibarr installation. Review the cPanel preparation plan before any separately approved installation work.

No production installation, tenant provisioning, database migration, removal of existing Accounting functionality, Laravel deletion or deployment is performed by this documentation.


The Provisioning architecture is documented as a design only. No Provisioning runtime, API, Registry database, tenant creation, subscription enforcement, SSO, cPanel change, DNS change or production rollout has been implemented by this repository documentation.

## Farnam content management

The Farnam homepage has an owner-only content editor at /farnam-admin. The authenticated primary account owner can edit the hero copy, services list (add/remove), about text, contact copy and phone number. Content is persisted in the Laravel database and rendered on the public guest homepage. Access is protected by the existing auth and account.owner middleware. This first CMS phase does not include media uploads, visual page-builder/reordering, multi-admin roles, lead inbox or production deployment; those require separate implementation and verification.
