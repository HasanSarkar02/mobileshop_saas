---
name: laravel-conventions
description: Laravel, Livewire, and code-structure conventions specific to this codebase's existing domain-module pattern. Trigger keywords - service, action, livewire component, migration, domain module, pint, larastan.
---

# Laravel & Code Conventions

This codebase already has an established DDD-flavored modular structure
under `app/Domain/*` (Inventory, Sales, Purchasing, Manufacturing,
Payroll, Approvals, Accounting, Notifications, Tenancy, Platform). New
code extends this structure; it does not introduce a competing one.

## Module shape to follow

```
app/Domain/<Module>/
  Models/
  Services/        <- business logic lives here
  Actions/          <- single-purpose, invokable operations (e.g. TenantProvisioningAction)
  Events/ Listeners/
  Policies/
```

## Services vs. Livewire components

- Livewire components: validate input, call a Service/Action, handle the
  UI result (flash message, redirect, re-render). No business rules.
- Services: the actual business logic, framework-agnostic where
  reasonable, unit-testable without booting Livewire.
- If a Livewire component's method is more than ~15-20 lines or contains
  an `if` on a business rule (discount eligibility, stock threshold,
  approval routing), that logic belongs in a Service, not the component.

## Migrations

- One migration per logical change; don't bundle unrelated schema
  changes together.
- Never edit a migration that has already run in a shared environment —
  write a new one, even to fix a typo in an already-shared migration
  filename (see the `2026_06_27_1025535_create_features_table.php` typo
  in the audit — the fix there is a rename commit before it's ever run
  anywhere shared, not after).
- Every new tenant-scoped table gets a `tenant_id` (and `branch_id` where
  relevant) foreign key with an index — isolation depends on this column
  actually being indexed, not just present.

## Formatting and static analysis

- Run `./vendor/bin/pint` before finishing any PHP change — this project
  should stay on default Laravel Pint style, don't introduce custom
  rules without discussion.
- If Larastan/PHPStan is configured, run it and resolve new errors it
  raises on touched files; don't suppress with a blanket ignore.

## Config and secrets

- `config/inventory.php` is currently git-ignored and untracked — any
  task touching it should also fix this (track it, move actual secrets
  to `.env`).
- `.env.example` must stay in sync with every new required env var
  (`CENTRAL_DOMAIN_URL`, `SMS_*`, `FCM_*`, etc.) — a missing entry here
  is an onboarding break for the next environment setup, not a cosmetic
  gap.

## Naming

- Actions are verbs: `CreateTenantAction`, `PostSaleToLedgerAction`.
- Services are nouns: `StockService`, `PayrollService`.
- Events are past tense: `SaleCompleted`, `StockAdjusted`.
- Booleans read as questions: `isLowStock()`, `hasPendingApproval()`.

## Package versions

Before using an API from Livewire, Tailwind, Spatie Permission, or any
other dependency, confirm the actual installed major version
(`composer show <pkg>`) rather than assuming behavior from a different
version's docs or training data — several of these packages (Livewire
4.x, Tailwind 4.x) have breaking changes from versions commonly seen in
older tutorials/training data.
