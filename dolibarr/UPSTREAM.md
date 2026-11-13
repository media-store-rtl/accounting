# Dolibarr upstream tracking

This directory tracks the official Dolibarr source repository. The Accounting application remains in the repository root; do not copy Dolibarr files over the Laravel application.

## Source of truth

- Repository: https://github.com/Dolibarr/dolibarr
- Release policy: official stable releases only
- Review policy: every detected release is proposed through a Pull Request
- Current tracked stable release: 24.0.3
- Release page: https://github.com/Dolibarr/dolibarr/releases/tag/24.0.3

## Important scope

The stable-release workflow updates this tracking record and opens a review PR when a newer non-draft, non-prerelease GitHub release is published. It does not download, replace, merge, or deploy Dolibarr source code. Source synchronization must be performed as a separately reviewed change after deciding the integration layout under dolibarr/upstream/, preserving Accounting's Laravel root and all local customizations.

Never treat a release-detection PR as proof that the Dolibarr source tree has been upgraded.

## Update procedure

1. Review the release notes and security advisories linked from the proposed PR.
2. Prepare the source synchronization in an isolated branch under dolibarr/upstream/; do not overwrite files belonging to the Accounting application.
3. Keep Medimo custom modules and patches separate from upstream files.
4. Run compatibility, installation, upgrade, API, tenant-isolation, and regression tests.
5. Merge only after explicit human approval. Production deployment is a separate, manually approved operation.
