---
name: testing-standards
description: Testing discipline, coverage bar, and test-first workflow for risk-bearing changes. Trigger keywords - test, phpunit, pest, coverage, factory, feature test, unit test.
---

# Testing Standards

23 feature tests exist for 105+ tables — that is thin for a system
carrying financial and inventory state for multiple businesses. The goal
is not test count for its own sake; it's proving the specific things
that are expensive to get wrong: tenant leaks, unauthorized access,
double-selling stock, incorrect ledger postings.

## Test-first is mandatory for four categories

For any change touching (1) tenant isolation, (2) authorization,
(3) money/ledger posting, or (4) stock quantity — the task is always two
steps, not one:

1. Write the test that demonstrates the current gap or would catch a
   regression. Run it. Confirm it fails (or would fail) against current
   code. Show that output.
2. Implement the fix/feature. Run the test again. Show it passing.

Skipping step 1 on these four categories is not a shortcut, it's an
unverified claim.

## Tenant-aware factories

Every factory for a tenant-scoped model must accept/require a tenant,
never default to "whatever's in context" — this makes cross-tenant test
setups explicit and prevents tests from accidentally validating against
a leaked global scope.

```php
Product::factory()->for($tenantB)->create();
```

## Coverage expectations

- Domain services (`app/Domain/*/Services`): target 80%+ line coverage.
  These carry the business rules; UI can be thinner.
- `tests/Unit` is currently empty — this is where pure calculation logic
  (costing methods, payroll component engine, approval policy resolution)
  belongs, without booting the full framework.
- Every Livewire component gets at least: a render test, a validation
  failure test, an authorization failure test (see authorization.md).
- Expenses domain currently has 0 tests — treat this as a Phase 0/1 gap,
  not a "later" item, since it touches money.

## Concurrency and idempotency

The stock allocator and SaleService are the two places a race condition
directly causes financial/inventory damage (double-selling the same
unit). Required test pattern: dispatch two allocation attempts against
the same limited-stock item concurrently (or simulate via a locked
transaction test) and assert only one succeeds.

## Export and report tests

`ReportExporter` currently has no test asserting the actual file
contents/structure of a generated PDF/Excel/CSV — only that the code
path runs. Add assertions on the exported file's row count or key
values, not just "no exception was thrown."

## Naming and structure

- Feature tests: `tests/Feature/<Domain>/<Behavior>Test.php`
- Unit tests: `tests/Unit/<Domain>/<Class>Test.php`
- Test method names state the behavior being proven, not the method
  under test: `test_cannot_allocate_more_stock_than_available()`, not
  `test_allocate_stock()`.

## Definition of done for "add tests" tasks

- [ ] Test fails against current code before the fix (pasted output)
- [ ] Test passes after the fix (pasted output)
- [ ] Full relevant suite still green, not just the new test
- [ ] `./vendor/bin/pint` clean
