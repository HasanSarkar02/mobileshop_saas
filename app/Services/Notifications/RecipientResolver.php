<?php

namespace App\Services\Notifications;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Customer;
use App\Services\Notifications\Data\ExternalRecipient;

/**
 * Turns "who should know about this" into a concrete list of Users.
 * Deliberately separate from NotificationDispatcher — listeners (which know
 * the domain) decide WHO; the dispatcher only knows HOW to fan a notification
 * out once it has a resolved list. Keeps both pieces small and reusable.
 */
class RecipientResolver
{
    /**
     * All active, system-access-enabled users in the shop holding $permission.
     * If $branchId is given, restricted to users assigned to that branch PLUS
     * branch-less users (Owner) — mirrors BranchScope's "null branch sees all"
     * rule exactly.
     *
     * Spatie permission checks are team-scoped via PermissionRegistrar, and
     * this may run from a queued job or console command with no ambient team
     * context set. Uses the same save/switch/restore pattern already
     * established in ShopRoleProvisioner::provisionForNewShop().
     *
     * @return Collection<int, User>
     */
    public function byPermission(Shop $shop, string $permission, ?int $branchId = null): Collection
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($shop->id);

        try {
            $users = User::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->where('has_system_access', true)
                ->when($branchId, fn ($q) => $q->where(
                    fn ($sq) => $sq->where('branch_id', $branchId)->orWhereNull('branch_id')
                ))
                ->get()
                ->filter(fn (User $user) => $user->can($permission))
                ->values();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }

        return $users;
    }

    /**
     * Explicit recipient list, filtered down to active/system-access users
     * only so a disabled or payroll-only local employee never ends up in a
     * delivery queue.
     *
     * @param  Collection<int, User|null>|array<int, User|null>  $users
     * @return Collection<int, User>
     */
    public function byUsers(Collection|array $users): Collection
    {
        return collect($users)
            ->filter()
            ->filter(fn (User $user) => $user->is_active && $user->has_system_access)
            ->unique('id')
            ->values();
    }

    /** The shop owner alone — used as the escalation apex. */
    public function owner(Shop $shop): Collection
    {
        $owner = User::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('user_type', 'owner')
            ->where('is_active', true)
            ->first();

        return $owner ? collect([$owner]) : collect();
    }

    public function customer(Customer $customer): Collection
    {
        return collect([
            new ExternalRecipient(
                phone: $customer->phone,
                email: $customer->email,
                name: $customer->name,
            ),
        ]);
    }
}