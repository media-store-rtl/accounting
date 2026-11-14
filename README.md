# Dolibarr Accounting

Dolibarr is the target primary product for this online accounting/ERP service. Customer registration, sign-in, user administration, roles, permissions, accounting operations and customer business data are intended to live directly in Dolibarr.

## Repository contents
- `dolibarr/upstream/`: official Dolibarr source, pinned as a Git submodule.
- `dolibarr/custom-modules/`: locally maintained Dolibarr modules.
- `dolibarr/patches/`: documented patches that cannot be implemented through supported extension points.
- `PROJECT_DEFINITION.md`: current product and architecture definition.
- `PROJECT_PROGRESS.md`: chronological project history and verified work status.

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
The current target is a separate Dolibarr database and isolated configuration, documents, sessions and runtime context for each customer. A controlled shared Dolibarr code release with separate customer environments is the current deployment recommendation. Dedicated customer subdomains are recommended for evaluation, not yet a finalized DNS decision.

No production installation, tenant provisioning, database migration, Laravel deletion or deployment is performed by this documentation.

## Deployment
The existing deployment automation and its production behavior must be reviewed separately before Dolibarr is installed or made the live product. A GitHub merge is not proof of production deployment.
