<?php

namespace Tests\Feature;

use App\Enums\ShopFeature;
use App\Enums\UserType;
use App\Livewire\Customers\CustomerProfile;
use App\Services\ShopFeatureService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Tenant isolation: impersonation + cross-shop access.
 *
 * REQUIRES MySQL (migrations use MySQL-specific SQL that sqlite cannot run):
 *   DB_CONNECTION=mysql DB_DATABASE=mobileshop_test php vendor/bin/phpunit --filter TenantIsolationTest
 * Skipped automatically on sqlite.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shopA;
    private Shop $shopB;
    private User $ownerA;
    private Customer $custA;
    private Customer $custB;
    private Purchase $purchaseB;

    protected function setUp(): void
    {
        // Must run BEFORE parent::setUp(): RefreshDatabase migrates in the
        // parent setup, and these migrations use MySQL-specific SQL that
        // sqlite cannot execute at all.
        $conn = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
        if ($conn !== 'mysql') {
            $this->markTestSkipped(
                'TenantIsolationTest requires MySQL. Run: ' .
                'DB_CONNECTION=mysql DB_DATABASE=mobileshop_test php vendor/bin/phpunit --filter TenantIsolationTest'
            );
        }

        parent::setUp();

        $this->shopA = $this->makeShop('Shop A', 'shop-a', 'a@test.shop');
        $this->shopB = $this->makeShop('Shop B', 'shop-b', 'b@test.shop');

        $branchA = $this->makeBranch($this->shopA, 'Main A', 'A-MAIN');
        $branchB = $this->makeBranch($this->shopB, 'Main B', 'B-MAIN');

        $this->ownerA = $this->makeUser('Owner A', 'owner-a@test.shop', $this->shopA, UserType::Owner);

        $this->custA = Customer::withoutGlobalScopes()->create([
            'shop_id' => $this->shopA->id, 'name' => 'Alpha Customer', 'phone' => '01111111111',
        ]);
        $this->custB = Customer::withoutGlobalScopes()->create([
            'shop_id' => $this->shopB->id, 'name' => 'Beta Customer', 'phone' => '02222222222',
        ]);

        $supplierB = Supplier::withoutGlobalScopes()->create([
            'shop_id' => $this->shopB->id, 'name' => 'Supplier B',
        ]);
        $this->purchaseB = Purchase::withoutGlobalScopes()->create([
            'shop_id' => $this->shopB->id,
            'branch_id' => $branchB->id,
            'supplier_id' => $supplierB->id,
            'reference_number' => 'PO-ISOLATION-B1',
            'purchase_date' => now()->format('Y-m-d'),
            'total_amount' => 5000,
            'payment_status' => 'unpaid',
        ]);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    private function makeShop(string $name, string $slug, string $email): Shop
    {
        $shop = Shop::withoutGlobalScopes()->create([
            'name' => $name, 'slug' => $slug, 'owner_name' => $name . ' Owner', 'email' => $email,
        ]);

        // Fresh shops have NULL enabled_features which ShopFeatureService
        // reads as [] (all gated features off) — enable everything so the
        // feature middleware doesn't 302 and mask isolation assertions.
        app(ShopFeatureService::class)->setFeatures(
            $shop->id,
            array_map(fn (ShopFeature $f) => $f->value, ShopFeature::cases())
        );

        return $shop;
    }

    private function makeBranch(Shop $shop, string $name, string $code): Branch
    {
        return Branch::withoutGlobalScopes()->create([
            'shop_id' => $shop->id, 'name' => $name, 'code' => $code,
            'is_main' => true, 'is_active' => true,
        ]);
    }

    private function makeUser(string $name, string $email, ?Shop $shop, UserType $type): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password' => bcrypt('password'),
            'shop_id' => $shop?->id,
            'user_type' => $type,
            'is_active' => true,
        ]);
    }

    public function test_null_context_lists_zero_rows_not_all_rows(): void
    {
        TenantContext::clear();

        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Purchase::count());
    }

    public function test_cross_shop_customer_url_does_not_render_the_record(): void
    {
        $response = $this->actingAs($this->ownerA)->get('/customers/' . $this->custB->id);

        // Tenant-scoped binding: foreign record is invisible (404). The
        // mount-level ownership guard returns 403 if binding ever resolves.
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_same_shop_customer_url_still_works(): void
    {
        $response = $this->actingAs($this->ownerA)->get('/customers/' . $this->custA->id);

        $response->assertOk();
        $response->assertSee('Alpha Customer');
        $response->assertDontSee('Beta Customer');
    }

    public function test_cross_shop_purchase_url_does_not_render_the_record(): void
    {
        $response = $this->actingAs($this->ownerA)->get('/purchases/' . $this->purchaseB->id);

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_mount_guard_aborts_403_on_foreign_model(): void
    {
        TenantContext::setShop($this->shopA->id);
        $this->actingAs($this->ownerA);

        try {
            (new CustomerProfile)->mount($this->custB);
            $this->fail('mount() with a foreign-shop customer must abort.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_mount_guard_aborts_403_with_null_context(): void
    {
        TenantContext::clear();
        $this->actingAs($this->ownerA);

        try {
            (new CustomerProfile)->mount($this->custA);
            $this->fail('mount() with null tenant context must abort (fail closed).');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_mount_guard_passes_for_own_model(): void
    {
        TenantContext::setShop($this->shopA->id);
        $this->actingAs($this->ownerA);

        // Must not throw: owner bypasses permission, ownership matches.
        (new CustomerProfile)->mount($this->custA);

        $this->assertTrue(true);
    }

    public function test_impersonation_scopes_to_target_shop_only(): void
    {
        $superAdmin = $this->makeUser('Super', 'super@test.shop', null, UserType::SuperAdmin);

        // Full impersonation handshake as the platform admin.
        $start = $this->actingAs($superAdmin, 'admin')
            ->post(route('admin.impersonate.start', $this->ownerA), ['reason' => 'isolation test']);

        $start->assertSessionHas('impersonation_log_id');
        $this->assertAuthenticatedAs($this->ownerA, 'web');

        // Now browsing as the impersonated tenant: own data visible…
        $list = $this->get('/customers');
        $list->assertOk();
        $list->assertSee('Alpha Customer');
        $list->assertDontSee('Beta Customer');

        // …and the other shop's record is unreachable.
        $this->get('/customers/' . $this->custB->id)->assertStatus(404);
        $this->get('/purchases/' . $this->purchaseB->id)->assertStatus(404);
    }
}
