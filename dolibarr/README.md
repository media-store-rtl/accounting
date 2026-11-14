# Dolibarr product source

Dolibarr is the primary product targeted by this repository's current architecture. Its official source is maintained as a pinned Git submodule.

- `upstream/`: official Dolibarr source; application document root is `upstream/htdocs/`.
- `custom-modules/`: locally maintained modules, kept outside upstream.
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

The current multi-customer direction is one independent Dolibarr database and isolated configuration, document storage and session context per customer. A shared controlled code release and dedicated subdomains are recommendations pending infrastructure validation, not completed deployment decisions.

Do not copy upstream files into another application root or edit the submodule checkout directly. No runtime installation or production deployment is implied by the presence of this source.
