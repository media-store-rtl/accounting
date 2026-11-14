# Dolibarr upstream

## Pinned source
- Official repository: https://github.com/Dolibarr/dolibarr
- Release policy: official stable releases only
- Current imported stable release: 24.0.3
- Release page: https://github.com/Dolibarr/dolibarr/releases/tag/24.0.3
- Upstream commit: `5c6c7ff6a5112413b1c27221c8d11750afad56f8`
- Integration method: Git submodule at `dolibarr/upstream`

This is the version pinned in Git, not evidence that this release is installed or running in production. The upstream repository remains the source of truth.

## Get the source
```bash
git clone --recurse-submodules --shallow-submodules https://github.com/media-store-rtl/accounting.git
```
For an existing clone:
```bash
git submodule update --init --depth 1 dolibarr/upstream
```
Dolibarr's application document root is `dolibarr/upstream/htdocs`.

## Product architecture
Dolibarr is the target primary product. Customer login, registration, user management and business operations belong directly to Dolibarr. Laravel Accounting is planned for retirement but must remain untouched until a separately approved migration/decommissioning plan is completed.

The current tenant direction is independent Dolibarr databases, configuration, document storage and session context per customer. A controlled shared code release and dedicated subdomains are recommended for evaluation; runtime topology, DNS and hosting remain subject to validation.

## Update policy
The GitHub Actions workflow checks official stable releases and opens a Pull Request for review. It does not merge or deploy. Review release notes, security changes, installation/upgrade compatibility, custom-module compatibility, tenant isolation, backup and rollback before accepting an update.

## Local customizations
- Keep custom modules in `dolibarr/custom-modules/`; deploy them into Dolibarr's `htdocs/custom/` only through a separately reviewed release process.
- Keep unavoidable upstream fixes in `dolibarr/patches/`, documenting target release, rationale and regression test.
- Never edit files inside `dolibarr/upstream/` directly.
- Do not interpret a submodule update as a production installation or tenant upgrade.

No runtime installation, database migration, tenant provisioning, Laravel deletion or production deployment is performed by this source-tracking workflow.
