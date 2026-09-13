---
name: tenant-isolation
description: Multi-tenant and branch data isolation rules for this Laravel ERP. Trigger keywords - tenant, branch, scope, isolation, multi-tenant, BelongsToTenant, cross-tenant, tenant_id.
---

# Tenant Isolation

This is a single-DB, shared-schema, multi-tenant SaaS. Every business's
data sits in the same tables, separated only by `tenant_id` (and
`branch_id` within a tenant). There is no database-level backstop like
Postgres RLS here — isolation is enforced entirely in application code.
Treat any gap in this as a data breach between customers, not a bug.

## The pattern: fail closed, not fail open

`BelongsToTenant` must add a global scope that:
- Reads the current tenant from `TenantContext` (already exists).
- If `TenantContext` has no tenant set, **throws**, it does not silently
  return unscoped (i.e. all-tenants) results. A missing context is a
  bug in the caller, and returning "everything" is the worst possible
  failure mode for a multi-tenant system.

```php
class BelongsToTenant extends Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null) {
            throw new TenantContextMissingException(
                'Attempted to query '.$model::class.' with no tenant context set.'
            );
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
```

## Queued jobs do not inherit request context

The single most common source of real-world tenant leaks: a job is
dispatched during a request (tenant context is set), but by the time a
worker picks it up, that context is gone. Every job touching
tenant-scoped data must explicitly carry and restore tenant_id.

```php
trait WithTenantContext
{
    public ?int $tenantId = null;

    public function withTenant(int $tenantId): static
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function handle(): void
    {
        if ($this->tenantId === null) {
            throw new TenantContextMissingException(static::class.' dispatched without a tenant.');
        }

        app(TenantContext::class)->setFor($this->tenantId);

        $this->run();
    }
}
```
Dispatch as `MyJob::dispatch(...)->withTenant($currentTenantId)` — never
assume the job will "just know."

## Console / Artisan commands

Any command that touches tenant data requires an explicit `--tenant=`
option with no default. A command that silently operates against "all
tenants" or "whatever happens to be in context" is a leak waiting to
happen during an ops task at 2am.

## Branch scoping

Branch is scoped the same way, one level down, but is not a security
boundary by itself — a branch-scoped query still passes through the
tenant scope first. Don't treat `branch_id` filtering as a substitute
for a missing `tenant_id` scope anywhere.

## Escape hatches must be visible and reviewed

```php
// SAFE: Platform admin cross-tenant report, gated by PlatformAdmin policy
$allTenants = Product::withoutGlobalScope(BelongsToTenant::class)->get();
```
Every `withoutGlobalScope()` call needs a `// SAFE: <reason>` comment
directly above it, and must be flagged to the security-reviewer subagent
before merge. No exceptions, including "just for a quick script."

## Testing requirement

Before implementing or changing anything in this area, write a test that
proves the leak exists (fails against current code), then make it pass.
Minimum required test shape:

```php
public function test_tenant_a_cannot_read_tenant_bs_products(): void
{
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $productB = Product::factory()->for($tenantB)->create();

    app(TenantContext::class)->setFor($tenantA->id);

    $this->assertNull(Product::find($productB->id));
    $this->assertFalse(Product::pluck('id')->contains($productB->id));
}
```

Also test: querying with no tenant context set throws, rather than
returning all rows.
