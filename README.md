# Dolibarr Accounting

Dolibarr is the target primary product for this online accounting/ERP service. Customer registration, sign-in, user administration, roles, permissions, accounting operations and customer business data are intended to live directly in Dolibarr.

## Repository contents
- `dolibarr/upstream/`: official Dolibarr source, pinned as a Git submodule.
- `dolibarr/custom-modules/`: locally maintained Dolibarr modules.
- `dolibarr/patches/`: documented patches that cannot be implemented through supported extension points.
- `PROJECT_DEFINITION.md`: current product and architecture definition.
- `PROJECT_PROGRESS.md`: chronological project history and verified work status.
- `docs/dolibarr-cpanel-deployment-preparation.md`: proposed cPanel tenant layout, deployment constraints, and future installation/acceptance checklist.

The Laravel Accounting application and its database have **not** been removed. They are transitional legacy resources that must remain intact until a separately approved migration, backup/restore, cutover and rollback plan is completed. This documentation change does not authorize destructive work.

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
The current target is a separate Dolibarr database and isolated configuration, documents, sessions and runtime context for each customer. A controlled, version-pinned release should be deployed into separate tenant application trees unless a shared physical code tree with isolated per-tenant configuration is proven safe under the actual runtime. Dedicated customer subdomains are recommended for evaluation, not yet a finalized DNS decision.

## Deployment
The current cPanel deployment file, `.cpanel.yml`, still contains Laravel deployment, Composer/npm build, Laravel migration, and seeder commands. It is not a Dolibarr deployment workflow and must not be treated as one or triggered for Dolibarr installation. Review the cPanel preparation plan before any separately approved installation work.

No production installation, tenant provisioning, database migration, Laravel deletion or deployment is performed by this documentation.
