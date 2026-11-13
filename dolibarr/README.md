# Dolibarr integration boundary

This directory contains the official Dolibarr source as a pinned Git submodule plus local extension/patch areas. It is intentionally isolated from the Laravel Accounting application at the repository root.

- `upstream/`: official Dolibarr repository, pinned to a reviewed stable tag. Its application document root is `upstream/htdocs/`.
- `custom-modules/`: locally maintained Dolibarr modules. These are separate from upstream and are deployed to `htdocs/custom/` only by a separately reviewed deployment process.
- `patches/`: minimal, documented patches for cases that cannot be implemented as a custom module or hook.

For a fresh clone, run `git submodule update --init --depth 1 dolibarr/upstream`, or clone with `--recurse-submodules --shallow-submodules`.

Do not copy Dolibarr files into the Laravel root, edit the submodule checkout directly, or assume that adding the source repository automatically connects the two applications at runtime.
