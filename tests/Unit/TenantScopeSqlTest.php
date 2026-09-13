<?php

namespace Tests\Unit;

use App\Http\Middleware\SetTenantContext;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Purchase;
use App\Support\TenantContext;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Tests\TestCase;

/**
 * Fail-closed tenant scope tests (DB-free: assert on generated SQL only,
 * so they run on sqlite in the default suite — no tables required).
 */
class TenantScopeSqlTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_shop_scope_returns_zero_rows_without_context(): void
    {
        TenantContext::clear();

        $sql = Customer::query()->toSql();

        $this->assertStringContainsString(
            '1 = 0',
            $sql,
            'ShopScope with null context must fail closed, not skip the WHERE clause.'
        );
    }

    public function test_branch_scope_returns_zero_rows_without_context(): void
    {
        TenantContext::clear();

        $sql = Purchase::query()->toSql();

        $this->assertStringContainsString('1 = 0', $sql);
    }

    public function test_global_or_shop_scope_returns_zero_rows_without_context(): void
    {
        TenantContext::clear();

        $sql = Brand::query()->toSql();

        $this->assertStringContainsString('1 = 0', $sql);
    }

    public function test_scopes_filter_normally_with_context(): void
    {
        TenantContext::setShop(7);
        TenantContext::setBranch(3);

        $customerSql = Customer::query()->toSql();
        $purchaseSql = Purchase::query()->toSql();
        $brandSql = Brand::query()->toSql();

        foreach ([$customerSql, $purchaseSql, $brandSql] as $sql) {
            $this->assertStringNotContainsString('1 = 0', $sql);
        }
        $this->assertStringContainsString('shop_id', $customerSql);
        $this->assertStringContainsString('shop_id', $purchaseSql);
        $this->assertStringContainsString('branch_id', $purchaseSql);
    }

    public function test_tenant_context_resolves_before_route_model_binding(): void
    {
        $priority = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewarePriority();

        $contextAt = array_search(SetTenantContext::class, $priority, true);
        $bindingsAt = array_search(SubstituteBindings::class, $priority, true);

        $this->assertNotFalse($contextAt, 'SetTenantContext must be in the middleware priority list.');
        $this->assertNotFalse($bindingsAt, 'SubstituteBindings must be in the middleware priority list.');
        $this->assertLessThan(
            $bindingsAt,
            $contextAt,
            'SetTenantContext must sort BEFORE SubstituteBindings so implicit bindings resolve tenant-scoped.'
        );
    }
}
