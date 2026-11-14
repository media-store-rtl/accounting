# Dolibarr first-tenant staging runbook

## Status and safety boundary

This runbook prepares a **disposable staging tenant** for the first Dolibarr deployment. It is a controlled, non-production rehearsal; it is not authorization to install or change the live cPanel account.

The only source release covered here is the repository's pinned Dolibarr 24.0.3 upstream commit `5c6c7ff6a5112413b1c27221c8d11750afad56f8`. Re-check the pin and upstream security/release notes before any installation.

Until a separate, explicit approval names the target environment and actions:

- Do not change production DNS, domains, document roots, TLS, or the live Laravel route.
- Do not create, alter, migrate, or delete production databases or users.
- Do not run the root `.cpanel.yml`; it deploys Laravel and runs Laravel build/migration/seeder commands.
- Do not modify Laravel code, data, files, jobs, cron entries, deployment, or configuration.
- Do not onboard real customer data or create the first paid customer's production tenant.
- Do not implement subscription integration or expired-plan read-only enforcement.
- Do not store credentials, `conf.php`, customer data, or backup contents in Git or CI logs.

The cPanel owner has confirmed that the required cPanel features are available. Exact PHP/database versions, extensions, limits, paths, permissions, and current production mappings still require read-only verification in the actual account at execution time.

## Verified upstream behavior relevant to tenant isolation

The following observations were checked against the pinned upstream source, not inferred from the project plan:

1. `htdocs/filefunc.inc.php` sets the default configuration path to `conf/conf.php` relative to the Dolibarr application tree. Each tenant therefore needs its own application/configuration tree unless a different config-loading design is separately engineered and proven.
2. `htdocs/conf/conf.php.example` defines installation-specific URL, application document root, data root, database host/type/name/user/password, instance unique ID, and a global database read-only switch.
3. `htdocs/main.inc.php` builds the session name from `DOLSESSID_` plus Dolibarr's instance prefix. Its default cookie path is `/` and it does not set a cookie domain in the standard path. `dol_getprefix()` documents a unique per-instance prefix. These facts are useful but do **not** replace browser tests on the actual PHP handler and chosen hostnames.
4. The installer writes the installation configuration. The installer flow must be run only against a disposable staging database and private data directory, over HTTPS, with no credentials copied into logs or source control.

Pinned source references:

- [filefunc.inc.php](https://github.com/Dolibarr/dolibarr/blob/5c6c7ff6a5112413b1c27221c8d11750afad56f8/htdocs/filefunc.inc.php)
- [conf.php.example](https://github.com/Dolibarr/dolibarr/blob/5c6c7ff6a5112413b1c27221c8d11750afad56f8/htdocs/conf/conf.php.example)
- [main.inc.php](https://github.com/Dolibarr/dolibarr/blob/5c6c7ff6a5112413b1c27221c8d11750afad56f8/htdocs/main.inc.php)
- [install/step1.php](https://github.com/Dolibarr/dolibarr/blob/5c6c7ff6a5112413b1c27221c8d11750afad56f8/htdocs/install/step1.php)
- [dol_getprefix() in functions.lib.php](https://github.com/Dolibarr/dolibarr/blob/5c6c7ff6a5112413b1c27221c8d11750afad56f8/htdocs/core/lib/functions.lib.php)

### Important distinction: database read-only flag

The upstream `dolibarr_main_db_readonly` setting disables SQL INSERT/UPDATE/DELETE and DDL statements globally. It is not the approved expired-subscription product policy, does not distinguish ordinary expiry from suspension, and must not be used as a substitute for a future application-level authorization design. That policy remains out of scope here.

## Staging topology

Use a dedicated, non-production hostname selected by the account owner (example only: `dolibarr-staging.example.invalid`). Do not assume that the example hostname or any production subdomain exists.

Use one disposable staging tenant for the first installation rehearsal. After it is healthy, provision a second disposable tenant to prove isolation. Each tenant must have:

- a separate application tree containing the same reviewed, immutable upstream release;
- its own `htdocs/conf/conf.php`, never shared or committed;
- its own database and dedicated database user;
- its own document/data root outside every public document root;
- a unique Dolibarr instance identifier and independently recorded configuration;
- independently verified session/cookie behavior;
- separate backup and restore evidence.

Suggested layout (illustrative only; verify actual account paths and cPanel document-root constraints before use):

```text
/home/ACCOUNT/dolibarr-staging/
  releases/24.0.3/<reviewed-upstream-artifact>/
  tenants/
    stage-a/
      htdocs/
        conf/conf.php              # private, tenant-specific
      documents/                   # private, outside public web access
      backup-staging/              # private, if permitted by hosting policy
    stage-b/
      htdocs/
        conf/conf.php              # private, tenant-specific
      documents/
      backup-staging/
```

A separate tenant application tree is the conservative cPanel approach. Do not use a shared physical `htdocs` tree with a per-tenant `conf.php` expectation: the standard upstream loader resolves `conf/conf.php` from that application tree. A shared-code optimization is deferred until a dedicated proof covers configuration, custom modules, sessions, cache, temporary files, and data paths.

## Phase 0 — read-only host preflight

Before creating anything, record non-secret evidence from the actual cPanel account:

- account home path and available terminal/SSH method;
- PHP version selected for the staging hostname and required extensions;
- database engine/version and supported driver (upstream example config lists `mysqli` and `pgsql`);
- database/user quotas and naming constraints;
- disk, inode, CPU, memory, process, upload, execution-time, and PHP memory limits;
- web server/PHP handler behavior and effective filesystem permissions;
- ability to place configuration, documents, and backup staging outside public web roots;
- staging hostname ownership, document-root mapping, and TLS readiness;
- existing production/Laravel mappings that must remain untouched.

Do not copy passwords, API keys, session identifiers, database dumps, or private customer data into this evidence record. If any prerequisite is incompatible or uncertain, stop before installation.

## Phase 1 — disposable tenant A installation rehearsal

Only after a separately approved staging operation:

1. Produce and checksum a reviewed artifact from the pinned upstream submodule. Do not edit files inside `dolibarr/upstream`.
2. Create a new private staging directory tree and verify that no path overlaps Laravel or an existing production document root.
3. Create a disposable database and a dedicated database user for tenant A. Do not reuse a Laravel or another tenant's database/user.
4. Deploy the reviewed source into tenant A's own application tree.
5. Configure the staging hostname's document root and TLS without changing production DNS or routes.
6. Run the upstream installer for tenant A only, using its own database and private data root. Keep credentials out of command history, browser screenshots, logs, and Git.
7. Verify the generated `conf.php` is private and inaccessible over HTTP; verify the data root and backup directory are also inaccessible over HTTP.
8. Apply the upstream installer-locking/hardening guidance and verify that the installer cannot be used again through the public URL.
9. Create only synthetic test records and files. Record release, paths, database identity (non-secret), and installation outcome.

## Phase 2 — tenant B isolation proof

Create tenant B as a second disposable installation with its own database/user, application tree, `conf.php`, data root, instance identifier, and hostname. Then execute and retain evidence for all tests:

| Test | Required result |
|---|---|
| Database identity | A and B use different databases and different DB users. |
| DB privilege boundary | Tenant A's DB user cannot access tenant B's database, and vice versa. |
| Configuration | Each hostname loads only its own `conf.php`; neither config can be fetched over HTTP. |
| Documents | A-created files are not visible/readable from B; neither tenant can traverse the other's data directory. |
| Sessions | Log in to both hostnames in one browser profile; verify independent session cookies/session state, and that logout or expiry in A does not invalidate B. |
| Cookie scope | Confirm actual cookie names, host/domain scope, path, Secure, HttpOnly, and SameSite attributes in the browser. Do not rely only on source inspection. |
| URL boundary | Directly request the other tenant's known URLs and file paths; access must be denied and must not disclose data. |
| Installer | Both public installer entry points are locked after installation. |
| Runtime paths | Cache, temporary files, generated files, and custom-module paths do not cross tenant boundaries. |
| Laravel non-interference | Existing Laravel files, DB, cron/jobs, deployment configuration, and production route remain unchanged. |

Any cross-tenant read, write, session reuse, config disclosure, or public access to private files is a blocking failure. Do not proceed to a production customer.

## Phase 3 — backup and restore rehearsal

For each disposable tenant independently:

1. Back up its database, documents, configuration, required encryption/instance keys, and custom modules using the approved private backup mechanism.
2. Verify backup completeness and record checksums without exposing secrets.
3. Restore into a new isolated disposable location/database, never over the source tenant or production.
4. Verify login, core records, document access, generated outputs, and key-dependent encrypted values.
5. Record restore duration, missing items, and actual restore result. A successful backup command without a successful restore test is not acceptance.

## Acceptance gate before any production request

Production installation is not accepted or authorized until all of the following are evidenced:

- [ ] Actual cPanel/PHP/database versions, extensions, limits, paths, and permissions recorded.
- [ ] Tenant A and B each have independent application/configuration, database/user, data root, and session context.
- [ ] All tenant-isolation tests pass in the actual browser/PHP runtime.
- [ ] Configuration, documents, installer, and backup staging are not publicly accessible.
- [ ] Backup and restore pass independently for both disposable tenants.
- [ ] Laravel and its database/files/jobs/cron/deployment remain unchanged.
- [ ] The owner has selected the real hostname/domain and approved its exact routing/TLS changes.
- [ ] A separate explicit approval names the production installation, tenant, database creation, DNS/cutover, and any other requested operation.

## Execution record template

Record only non-secret values:

- Date/time and operator:
- Staging hostname:
- cPanel account path (no credentials):
- PHP version and required extensions:
- Database engine/version and driver:
- Tenant A/B application, document, and backup paths:
- Tenant A/B database identifiers (no passwords):
- Dolibarr version and upstream commit:
- Installer-lock verification:
- Cross-tenant DB/files/session/URL test results:
- Backup and restore test results:
- Laravel non-interference verification:
- Failures, remediation, and retest:
- Explicit approval reference for any operation performed:

This document prepares a staging rehearsal only. It does not claim that cPanel was accessed, that a tenant/database was created, that Dolibarr was installed, or that any live environment was changed.
