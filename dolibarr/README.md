# Dolibarr product source

Dolibarr is the primary product targeted by this repository's current architecture. Its official source is maintained as a pinned Git submodule.

- `upstream/`: official Dolibarr source; application document root is `upstream/htdocs/`.
- `custom-modules/`: locally maintained Dolibarr modules, kept outside upstream.
- `patches/`: minimal, documented patches for cases unsupported by extension points.

Get the complete source with:
```bash
git clone --recurse-submodules --shallow-submodules https://github.com/media-store-rtl/accounting.git
```
For an existing clone:
```bash
git submodule update --init --depth 1 dolibarr/upstream
```

The target architecture uses Dolibarr directly for customer registration, login, user administration and accounting/ERP operations. Laravel Accounting is planned for retirement, but neither its application nor its database has been deleted. Removal requires a separate approved migration and backup/rollback plan.

## cPanel deployment preparation

See [the cPanel deployment preparation plan](../docs/dolibarr-cpanel-deployment-preparation.md) before any installation work. The current plan proposes independent tenant application trees, databases, configuration, document storage and session context. The same reviewed upstream release may be replicated into each tenant tree; a single shared physical `htdocs` tree is not assumed safe because `htdocs/conf/conf.php` is installation-specific.

The repository's root `.cpanel.yml` remains Laravel-specific. It runs Laravel deployment/build/migration commands and must not be reused as a Dolibarr deployment script.

The preparation plan is documentation only. It does not install Dolibarr, create tenants or databases, change cPanel/DNS, or authorize Laravel removal.
