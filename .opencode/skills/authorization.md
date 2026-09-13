---
name: authorization
description: Authorization and access-control rules for routes, Livewire components, and the API guard. Trigger keywords - policy, authorize, permission, role, gate, can middleware, Sanctum.
---

# Authorization

Currently only one `authorize()` call exists in the whole codebase
(`PurchaseOrderShow.php`). The sidebar hides unavailable features, but
that is UX, not security — any authenticated user who knows or guesses a
URL can currently reach a Livewire component the sidebar hides from them.
Fix this as a structural pattern, not component-by-component.

## Base trait for every Livewire component

```php
trait AuthorizesTenantAccess
{
    public function mount(): void
    {
        $this->authorizeAccess();
        parent::mount();
    }

    abstract protected function authorizeAccess(): void;
}
```
Every Livewire component extends/uses this and implements
`authorizeAccess()` with an explicit `$this->authorize(...)` or
`abort_unless(...)` call — never an empty override. A component with no
real check in `authorizeAccess()` should fail code review, not pass it
by omission.

```php
class SupplierPaymentIndex extends Component
{
    use AuthorizesTenantAccess;

    protected function authorizeAccess(): void
    {
        $this->authorize('viewAny', SupplierPayment::class);
    }
}
```

## Policies, not ad hoc role checks

Use Spatie Permission's role/permission strings inside real Policy
classes, not scattered `auth()->user()->hasRole('admin')` checks inline
in components or views. A Policy is testable in isolation and is the
single place the rule lives.

```php
class SupplierPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.payments.create')
            && FeatureGate::enabled($user->tenant, FeatureKey::Purchasing);
    }
}
```

## Route-level enforcement

Every route in `routes/web.php` needs `can:` middleware matching its
Policy method — this is the backstop if a Livewire mount check is ever
missed, and it's what stops the direct-URL bypass named in the audit.

```php
Route::get('/purchasing/payments', SupplierPaymentIndex::class)
    ->middleware(['auth', 'can:viewAny,App\Models\SupplierPayment']);
```

## API guard and Sanctum

Sanctum is installed but `auth.php` has no `api` guard and tokens don't
expire. For any API surface:
- Add the `api` guard explicitly.
- Set a token expiration (`Sanctum::personalAccessTokenExpiration`), do
  not leave tokens valid forever.
- Scope tokens with abilities (`createToken('name', ['reports:read'])`)
  rather than issuing a single all-access token per user.

## Negative tests are the actual proof

A positive test ("admin can view the page") does not prove authorization
works. Required test shape for every gated component/route:

```php
public function test_user_without_permission_cannot_view_supplier_payments(): void
{
    $user = User::factory()->withoutPermission('purchasing.payments.view')->create();

    $this->actingAs($user)
        ->get(route('purchasing.payments.index'))
        ->assertForbidden();
}
```
If this test doesn't exist for a gated resource, the gating is unproven.
