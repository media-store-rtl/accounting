# Dolibarr upstream

## Pinned source

- Official repository: https://github.com/Dolibarr/dolibarr
- Release policy: official stable releases only
- Current imported stable release: 24.0.3
- Release page: https://github.com/Dolibarr/dolibarr/releases/tag/24.0.3
- Upstream commit: `5c6c7ff6a5112413b1c27221c8d11750afad56f8`
- Integration method: Git submodule at `dolibarr/upstream`

The source is pinned to the official release tag, not to the moving development branch. The upstream repository remains the source of truth.

## Get the source

Clone this repository with submodules:

```bash
git clone --recurse-submodules https://github.com/media-store-rtl/accounting.git
```

If the repository is already cloned:

```bash
git submodule update --init --depth 1 dolibarr/upstream
```

The Dolibarr application document root is `dolibarr/upstream/htdocs`. The Laravel Accounting application remains in the repository root and is not overwritten or coupled to Dolibarr's bootstrap.

## Update policy

The GitHub Actions workflow checks the official latest release weekly and on manual dispatch. It rejects drafts and prereleases, resolves the release tag to its commit, updates the submodule pointer and this record on a dedicated branch, and opens a Pull Request. It never merges or deploys.

A source update PR must be reviewed for release notes, security changes, installation/upgrade compatibility, API compatibility, custom-module compatibility, and tenant/entity isolation. Laravel tests alone do not prove Dolibarr compatibility.

## Local customizations

- Keep custom Dolibarr modules in `dolibarr/custom-modules/`; deploy them into Dolibarr's `htdocs/custom/` through a separately reviewed deployment process.
- Keep unavoidable upstream fixes as patch files in `dolibarr/patches/`, each documented with its target release, rationale, and regression test.
- Do not edit files inside `dolibarr/upstream/` directly. Changes there are upstream-owned and will be replaced by the next submodule update.
- Keep the Laravel application, its migrations, and its deployment independent of the Dolibarr submodule.

No Dolibarr runtime installation, database migration, production deployment, or automatic merge is performed by this integration.
