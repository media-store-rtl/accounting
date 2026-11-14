# cPanel deployment preparation for Dolibarr

## Status and scope

This document is a deployment preparation plan, not an installation procedure that has been executed. The account owner has confirmed that the current cPanel hosting account supports the required operations (PHP selection, database creation, subdomains, SSL, scheduled jobs, and terminal/SSH as applicable). Exact limits, versions, PHP extensions, and filesystem permissions still need to be read from the live account before a production rollout.

The current repository pins official Dolibarr 24.0.3 at upstream commit `5c6c7ff6a5112413b1c27221c8d11750afad56f8`. This is a source pin only; it does not prove that Dolibarr is installed on the hosting account.

No server, cPanel, DNS, database, Laravel deployment, or production setting is changed by this plan.

## Deployment constraints

1. Dolibarr is the direct product. Customer registration, login, user administration, roles, and business operations belong in Dolibarr.
2. Each customer must have a separate Dolibarr database, database credentials, configuration, document storage, and session/cookie context.
3. A customer subdomain is the intended routing pattern, subject to the final domain decision and DNS/TLS validation.
4. The existing Laravel Accounting application, database, files, cron jobs, and deployment must remain untouched until a separately approved migration and decommissioning plan is completed.
5. The current root `.cpanel.yml` deploys Laravel and runs Laravel Composer/npm/migration/seeder commands. It must not be reused as a Dolibarr deployment script. Do not trigger it as part of Dolibarr preparation.
6. Do not implement subscription enforcement or expired-plan read-only behavior in this deployment-preparation task. Those require a separately approved design and implementation task.

## Proposed cPanel filesystem layout

The following is a proposed layout to validate against the hosting account. Replace the example account path and tenant keys only during a separately approved implementation:

```text
/home/ACCOUNT/
  dolibarr/
    releases/
      24.0.3/
        upstream-source/              # immutable source artifact from the pinned upstream commit
    tenants/
      TENANT_KEY/
        htdocs/                       # tenant-specific deployed Dolibarr htdocs tree
          conf/
            conf.php                  # tenant-specific database and data-root configuration
        documents/                    # tenant-specific generated documents and uploads
        private-backups/              # access-restricted backup staging, if hosting policy permits
  public_html/                         # existing unrelated site content; do not overwrite
```

For each tenant, configure its cPanel subdomain's document root to that tenant's `htdocs/` directory (or to a dedicated public directory containing only the intended web entry point if later security review chooses that layout). Keep `documents/` and backup staging outside every web-accessible document root.

### Important code-isolation decision

Do not point multiple tenants at one physical Dolibarr `htdocs/` tree while expecting each tenant to have a different `htdocs/conf/conf.php`: that configuration file is installation-specific. The conservative cPanel plan is to deploy the same reviewed, version-pinned upstream release into a separate tenant application tree for each customer, with tenant-specific configuration and data directories. This duplicates application files but avoids relying on unproven per-request configuration switching, symlink behavior, or shared mutable configuration.

A future shared-code optimization may be considered only after a runtime proof demonstrates isolated configuration, sessions, cache, temporary files, custom modules, and data paths under the actual cPanel/PHP handler. It is not part of this plan.

## Tenant resource map

Each tenant must have a recorded mapping containing:

- tenant key and assigned subdomain;
- deployed Dolibarr release and exact upstream commit;
- cPanel document root;
- unique database name and database user (never shared between tenants);
- tenant-specific `conf.php` and secret-handling location;
- absolute document-storage path outside the web root;
- PHP version and required extensions;
- session/cookie namespace and any tenant-specific cache/temp paths;
- backup schedule, retention policy, last successful backup, and last restore-test result;
- installation/upgrade state and operator audit trail.

Never place database passwords, API secrets, or customer data in Git, this repository, deployment logs, or public directories.

## Installation sequence — future approved operation

1. Confirm the actual cPanel account path, PHP version, required PHP extensions, database engine/version, per-account database limits, disk/inode limits, and available execution methods.
2. Confirm the domain/subdomain and TLS plan. Do not change DNS until the target document root and rollback route are agreed.
3. Verify the pinned upstream source and its license/release notes; produce a reviewed release artifact from the Git submodule. Do not edit files inside the upstream submodule.
4. Create the tenant's database and dedicated database user through the approved cPanel mechanism. Grant only the permissions required by Dolibarr installation and operation; do not use a shared tenant DB user.
5. Create the tenant filesystem tree and permissions. Keep documents and backups outside the web root and prevent other tenants from reading them.
6. Deploy the reviewed Dolibarr `htdocs` tree into that tenant's application directory. Create that tenant's `conf/conf.php` only during the approved install operation.
7. Point the tenant subdomain document root to the agreed web directory and enable TLS.
8. Run the Dolibarr web installer for that tenant only. Record the resulting release, database, paths, and installation status without recording secrets.
9. Apply the upstream installation hardening guidance, including locking the installer after successful setup. Confirm that configuration and private documents cannot be downloaded over HTTP.
10. Verify tenant isolation using two test tenants: database separation, file access denial, session/cookie separation, and inability to access another tenant's URL or resources.
11. Test backup and restore into a non-production test location before onboarding real customer data.
12. Repeat independently for each tenant. Never clone a live tenant database or copy its `conf.php` to another tenant.

## Upgrade and rollback model

- Prepare and review a new immutable release artifact for a specific upstream version/commit.
- Back up each tenant's database, documents, configuration, and custom modules before upgrading that tenant.
- Upgrade and verify tenants individually; do not assume a successful upgrade of one tenant proves all tenants are healthy.
- Keep the previous release and a tested rollback procedure available. Database schema rollback may require restoring the tenant's pre-upgrade database backup; switching application files alone is not necessarily sufficient.
- Do not automatically upgrade all tenant databases from a source-sync workflow or a cPanel Git deployment hook.

## Acceptance checklist before production

- [ ] cPanel PHP version and required extensions verified against the pinned Dolibarr release.
- [ ] Database engine/version and per-account database/user limits verified.
- [ ] Actual storage, inode, CPU, memory, process, and execution-time limits recorded.
- [ ] Subdomain document root and TLS verified without changing the live product route.
- [ ] Private documents, configuration, and backups are outside public web access.
- [ ] Two-tenant database, filesystem, session, and URL isolation tests pass.
- [ ] Backup and restore test passes for a disposable tenant.
- [ ] Installer lock and file permissions verified.
- [ ] Existing Laravel deployment and resources remain intact.
- [ ] Separate approval obtained for any production installation, DNS change, cutover, or Laravel retirement.

## Official references

- [Dolibarr installation and upgrade guide](https://wiki.dolibarr.org/index.php/Installation_/_Setup) — includes shared-hosting installation guidance and installer locking.
- [Dolibarr prerequisites](https://wiki.dolibarr.org/index.php/Prerequisites) — web server, PHP, database, and storage prerequisites.
- [Pinned upstream source](https://github.com/Dolibarr/dolibarr/tree/5c6c7ff6a5112413b1c27221c8d11750afad56f8).

These references describe general Dolibarr requirements and installation behavior. They do not verify the configuration or security of this particular cPanel account.
