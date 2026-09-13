---
name: git-workflow
description: Git commit discipline, branching, and merge-readiness rules. Trigger keywords - commit, git, branch, pull request, PR, push, merge.
---

# Git Workflow

Current state per the audit: 7 commits over 36 days, then a one-month
stall with 15 modified and 23 untracked files sitting uncommitted. Loose
git hygiene on a solo/small-team project compounds fast once an agent is
also generating changes — uncommitted state is exactly where "what did
the last session actually do" becomes unanswerable.

## Commit per task, not per session

One task from `PROGRESS.md` = one commit (or a small tight sequence of
commits if the task naturally splits into test-then-implementation).
Never let a session end with uncommitted working-tree changes sitting
unexplained — either commit it or explicitly note in `PROGRESS.md` why
it's intentionally left uncommitted.

## Commit message format

Conventional Commits, referencing the module and the "why":

```
feat(inventory): add tenant scope enforcement to BelongsToTenant

Global scope now throws when TenantContext has no tenant set instead of
returning unscoped results. Closes the leak identified in Phase 0 audit.

Tests: TenantIsolationTest (new, passing)
```

Prefixes: `feat`, `fix`, `test`, `refactor`, `chore`, `docs`, `security`.
Use `security(...)` explicitly for anything touching tenant isolation,
authorization, or audit trail — it makes these changes greppable in
history for a future compliance review.

## Before every commit

- [ ] `./vendor/bin/pint` clean
- [ ] Relevant tests pass — paste the output in the session, not just in
      the commit message
- [ ] No `.env`, no real credentials, no `config/inventory.php` secrets
      committed
- [ ] `PROGRESS.md` updated in the same commit (or immediately after)

## Branching

- `main` stays deployable. Feature/fix work happens on a branch named
  `phase-<n>/<short-description>` (e.g. `phase-0/tenant-scope`).
- Phase 0/2 changes (tenant isolation, authorization, audit) route
  through the `security-reviewer` subagent before merge to `main` — run
  it, paste its verdict, only merge on `MERGE-READY: yes`.
- Squash-merge feature branches to keep `main` history readable; keep
  the granular commits on the branch itself for review.

## Migrations and git

Never rewrite/force-push a migration file that has already been merged
to `main` — treat merged migrations as immutable, same rule as
production migrations (see laravel-conventions.md). Fix mistakes with a
new migration.

## What NOT to do

- Don't accumulate a week of changes before committing "to keep it
  clean" — that's the opposite of clean; it's an unreviewable diff.
- Don't commit directly to `main` for Phase 0/2 work, even solo — the
  branch + reviewer step is what makes "was this actually checked"
  answerable later.
