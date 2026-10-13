# Agent Instructions

These instructions apply to every AI assistant or coding agent working in this repository. Follow them at the start of every new task and across future sessions.

## Repository and required context

- Repository: https://github.com/media-store-rtl/accounting
- Before doing any work, read `PROJECT_PROGRESS.md`, this file (`AGENTS.md`), `README.md`, and the relevant project-definition/technical documentation (including `PROJECT_DEFINITION.md` when present).
- Treat `PROJECT_PROGRESS.md` as the project history, but do not assume every status note is current. Verify important claims against the live GitHub state.
- Check the actual current state of `main`, relevant branches, open pull requests, recent commits, and the latest CI/check runs. Do not rely only on chat history or old notes.
- Inspect the relevant implementation and existing tests before choosing a solution. Follow the repository's existing architecture and conventions.

## Required working process

1. Clarify the requested outcome when it is materially ambiguous; otherwise proceed with the task rather than only giving suggestions.
2. Inspect the relevant code, migrations, routes, views, tests, and project documentation before editing.
3. Make changes on a dedicated working branch. Never commit task changes directly to `main`.
4. Add or update tests appropriate to the change.
5. Run the relevant tests and any additional project checks that are practical in the available environment.
6. If a test/check fails, investigate the cause, fix it when feasible, and rerun it. Report unresolved failures explicitly.
7. Update `PROJECT_PROGRESS.md` as part of the same work item. Record the request, behavior changed, files changed, migration/server actions, tests added and actual results, browser/live-site status, branch, commit, PR, CI, and merge status as applicable.
8. Commit the changes on the working branch and open a pull request when appropriate. Include a clear summary and test results.
9. Before any merge, give the user a concise summary of the changes and real verification results and obtain explicit approval for that merge.
10. After an approved merge, verify the actual merged state and merge commit SHA on GitHub, then update/check the progress record accordingly. If access or tooling prevents this, say so; do not bypass the restriction.

## Safety and reporting rules

- Never merge or close a pull request without the user's explicit permission. Approval to implement or open a PR is not approval to merge it.
- Respect existing user instructions for individual PRs. In particular, do not merge or close PR #52 unless the user explicitly changes that instruction.
- Never commit directly to `main`; use a separate branch and PR.
- Do not claim that tests passed unless they were actually run and the output or CI check confirms success. Distinguish local test results from CI results.
- Do not claim that a migration was run, the browser was checked, the live website was tested, or a deployment occurred unless there is direct evidence that it actually happened.
- GitHub merge is not proof of production deployment. The README mentions automated deployment monitoring, but never infer a successful deployment from a commit or merge alone.
- If required access, tools, dependencies, credentials, or runtime are unavailable, state the limitation clearly and do not pretend the work was completed.
- Report at the end: summary, changed files, tests actually run and results, CI/check status, branch/commit/PR links, merge status, and anything not verified.
- Keep the user's language and communication style in mind; the user normally communicates in Persian.

## Initial inspection-only requests

If the user asks to inspect the project/status first and says not to make changes yet, only inspect and report. Do not edit, commit, create a branch for implementation, or open a PR until the user authorizes the next step.

## Progress log is mandatory

Every code or documentation change must be accompanied by an update to `PROJECT_PROGRESS.md` in the same branch/PR. Do not wait for the user to remind you. Explicitly mark tests, browser checks, migrations, and deployment as not run/not verified when applicable.
