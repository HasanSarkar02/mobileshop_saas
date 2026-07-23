<?php

namespace App\Livewire\Admin;

use App\Enums\ShopStatus;
use App\Models\AdminActionLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\ImpersonationService;
use App\Services\UserInviter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Shop Detail')]
class ShopDetail extends Component
{
    public int $shopId;

    public bool $showEditForm = false;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $businessType = 'mobile_shop';
    public ?string $trialEndsAt = null;

    public bool $vatEnabled = false;
    public string $vatRegistrationNumber = '';
    public string $defaultVatRate = '0';

    public string $website = '';
    public string $tradeLicenseNumber = '';
    public string $documentFooterNote = '';
    public bool $showDocumentConfidential = false;

    public ?int $ownerId = null;
    public string $ownerName = '';
    public string $ownerEmail = '';
    public string $ownerPhone = '';
    public bool $ownerIsActive = true;

    // ── Suspend modal ────────────────────────────────────────────────────
    public bool $showSuspendModal = false;
    public string $suspendReason = '';

    // ── Subscription date edit ───────────────────────────────────────────
    public bool $editingSubscription = false;
    public string $subTrialEndsAt = '';
    public string $subCurrentPeriodEnd = '';
    public string $subNextBillingDate = '';

    // ── Books lock edit ──────────────────────────────────────────────────
    public bool $editingBooksLock = false;
    public string $booksLockedThroughInput = '';

    public function mount(Shop $shop): void
    {
        $this->shopId = $shop->id;
    }

    public function activate(AdminAuditLogger $audit): void
    {
        $shop = Shop::withoutGlobalScopes()->findOrFail($this->shopId);
        $shop->update([
            'status' => ShopStatus::Active,
            'is_active' => true,
            'suspension_reason' => null,
            'suspended_at' => null,
        ]);

        $audit->log(Auth::guard('admin')->user(), 'shop.activated', $shop);

        $this->dispatch('notify', type: 'success', message: 'Shop activated.');
    }

    public function openSuspendModal(): void
    {
        $this->suspendReason = '';
        $this->showSuspendModal = true;
    }

    public function confirmSuspend(AdminAuditLogger $audit): void
    {
        $this->validate(['suspendReason' => 'required|string|min:3|max:500']);

        $shop = Shop::withoutGlobalScopes()->findOrFail($this->shopId);
        $shop->update([
            'status' => ShopStatus::Suspended,
            'is_active' => false,
            'suspension_reason' => $this->suspendReason,
            'suspended_at' => now(),
        ]);

        $audit->log(Auth::guard('admin')->user(), 'shop.suspended', $shop, $this->suspendReason);

        $this->showSuspendModal = false;
        $this->dispatch('notify', type: 'warning', message: 'Shop suspended.');
    }

    public function impersonate(ImpersonationService $service): void
    {
        $shop = Shop::withoutGlobalScopes()->with('owner')->findOrFail($this->shopId);

        if (! $shop->owner) {
            $this->dispatch('notify', type: 'error', message: 'No owner found for this shop.');
            return;
        }

        $service->start(request(), Auth::guard('admin')->user(), $shop->owner, 'Admin initiated via shop detail page.');

        $this->redirect('/dashboard', navigate: true);
    }

    // ── Edit shop/owner info ─────────────────────────────────────────────

    public function startEdit(): void
    {
        $shop = Shop::withoutGlobalScopes()->with('owner')->findOrFail($this->shopId);

        $this->name                     = $shop->name;
        $this->email                    = $shop->email;
        $this->phone                    = $shop->phone ?? '';
        $this->address                  = $shop->address ?? '';
        $this->businessType             = $shop->business_type;
        $this->trialEndsAt              = $shop->trial_ends_at?->format('Y-m-d');
        $this->vatEnabled               = (bool) $shop->vat_enabled;
        $this->vatRegistrationNumber    = $shop->vat_registration_number ?? '';
        $this->defaultVatRate           = (string) $shop->default_vat_rate;
        $this->website                  = $shop->website ?? '';
        $this->tradeLicenseNumber       = $shop->trade_license_number ?? '';
        $this->documentFooterNote       = $shop->document_footer_note ?? '';
        $this->showDocumentConfidential = (bool) $shop->show_document_confidential;

        if ($shop->owner) {
            $this->ownerId       = $shop->owner->id;
            $this->ownerName     = $shop->owner->name;
            $this->ownerEmail    = $shop->owner->email;
            $this->ownerPhone    = $shop->owner->phone ?? '';
            $this->ownerIsActive = (bool) $shop->owner->is_active;
        } else {
            $this->ownerId       = null;
            $this->ownerName     = '';
            $this->ownerEmail    = '';
            $this->ownerPhone    = '';
            $this->ownerIsActive = true;
        }

        $this->resetErrorBag();
        $this->showEditForm = true;
    }

    public function cancelEdit(): void
    {
        $this->showEditForm = false;
        $this->resetErrorBag();
    }

    public function updateShop(): void
    {
        $rules = [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:shops,email,'.$this->shopId,
            'phone'                 => 'nullable|string|max:20',
            'address'               => 'nullable|string|max:500',
            'businessType'          => 'required|in:mobile_shop,electronics,general_retail',
            'trialEndsAt'           => 'nullable|date',
            'vatRegistrationNumber' => 'nullable|string|max:100',
            'defaultVatRate'        => 'nullable|numeric|min:0|max:100',
            'website'               => 'nullable|url|max:255',
            'tradeLicenseNumber'    => 'nullable|string|max:100',
            'documentFooterNote'    => 'nullable|string|max:1000',
        ];

        $data = [
            'name'                  => $this->name,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'address'               => $this->address,
            'businessType'          => $this->businessType,
            'trialEndsAt'           => $this->trialEndsAt,
            'vatRegistrationNumber' => $this->vatRegistrationNumber,
            'defaultVatRate'        => $this->defaultVatRate,
            'website'               => $this->website,
            'tradeLicenseNumber'    => $this->tradeLicenseNumber,
            'documentFooterNote'    => $this->documentFooterNote,
        ];

        if ($this->ownerId) {
            $rules['ownerName']  = 'required|string|max:255';
            $rules['ownerEmail'] = 'required|email|max:255|unique:users,email,'.$this->ownerId;
            $rules['ownerPhone'] = 'nullable|string|max:20';

            $data['ownerName']  = $this->ownerName;
            $data['ownerEmail'] = $this->ownerEmail;
            $data['ownerPhone'] = $this->ownerPhone;
        }

        $validated = Validator::make($data, $rules)->validate();

        Shop::withoutGlobalScopes()->where('id', $this->shopId)->update([
            'name'                       => $validated['name'],
            'email'                      => $validated['email'],
            'phone'                      => $validated['phone'] ?: null,
            'address'                    => $validated['address'] ?: null,
            'business_type'              => $validated['businessType'],
            'trial_ends_at'              => $validated['trialEndsAt'] ?: null,
            'vat_enabled'                => $this->vatEnabled,
            'vat_registration_number'    => $this->vatEnabled ? ($validated['vatRegistrationNumber'] ?: null) : null,
            'default_vat_rate'           => $this->vatEnabled ? ($validated['defaultVatRate'] ?: 0) : 0,
            'website'                    => $validated['website'] ?: null,
            'trade_license_number'       => $validated['tradeLicenseNumber'] ?: null,
            'document_footer_note'       => $validated['documentFooterNote'] ?: null,
            'show_document_confidential' => $this->showDocumentConfidential,
        ]);

        if ($this->ownerId) {
            User::where('id', $this->ownerId)->update([
                'name'      => $validated['ownerName'],
                'email'     => $validated['ownerEmail'],
                'phone'     => $validated['ownerPhone'] ?: null,
                'is_active' => $this->ownerIsActive,
            ]);
        }

        $this->showEditForm = false;
        $this->dispatch('notify', type: 'success', message: 'Shop information updated.');
    }

    public function resendOwnerInvite(UserInviter $inviter): void
    {
        $shop = Shop::withoutGlobalScopes()->with('owner')->findOrFail($this->shopId);

        if (! $shop->owner) {
            $this->dispatch('notify', type: 'error', message: 'No owner found for this shop.');
            return;
        }

        $inviter->invite($shop->owner, "the owner of {$shop->name}");

        $this->dispatch('notify', type: 'success', message: "Invite email resent to {$shop->owner->email}.");
    }

    // ── Subscription date edit ───────────────────────────────────────────

    public function startEditSubscription(): void
    {
        $shop = Shop::withoutGlobalScopes()->with('activeSubscription')->findOrFail($this->shopId);
        $sub = $shop->activeSubscription;

        if (! $sub) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'This shop has no active subscription to edit.']);
            return;
        }

        $this->subTrialEndsAt      = $sub->trial_ends_at?->format('Y-m-d') ?? '';
        $this->subCurrentPeriodEnd = $sub->current_period_end?->format('Y-m-d') ?? '';
        $this->subNextBillingDate  = $sub->next_billing_date?->format('Y-m-d') ?? '';
        $this->editingSubscription = true;
    }

    public function saveSubscriptionDates(AdminAuditLogger $audit): void
    {
        $this->validate([
            'subTrialEndsAt'      => 'nullable|date',
            'subCurrentPeriodEnd' => 'nullable|date',
            'subNextBillingDate'  => 'nullable|date',
        ]);

        $shop = Shop::withoutGlobalScopes()->with('activeSubscription')->findOrFail($this->shopId);
        $sub  = $shop->activeSubscription;

        if (! $sub) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No active subscription found.']);
            return;
        }

        $sub->update([
            'trial_ends_at'      => $this->subTrialEndsAt ?: null,
            'current_period_end' => $this->subCurrentPeriodEnd ?: null,
            'next_billing_date'  => $this->subNextBillingDate ?: null,
        ]);

        $audit->log(Auth::guard('admin')->user(), 'subscription.dates_manually_edited', $shop, 'Manual expiry override', [
            'subscription_id'     => $sub->id,
            'trial_ends_at'       => $this->subTrialEndsAt,
            'current_period_end'  => $this->subCurrentPeriodEnd,
            'next_billing_date'   => $this->subNextBillingDate,
        ]);

        $this->editingSubscription = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Subscription dates updated.']);
    }

    // ── Books lock edit ──────────────────────────────────────────────────

    public function startEditBooksLock(): void
    {
        $shop = Shop::withoutGlobalScopes()->findOrFail($this->shopId);
        $this->booksLockedThroughInput = $shop->books_locked_through?->format('Y-m-d') ?? '';
        $this->editingBooksLock = true;
    }

    public function saveBooksLock(AdminAuditLogger $audit): void
    {
        $this->validate(['booksLockedThroughInput' => 'nullable|date']);

        $shop = Shop::withoutGlobalScopes()->findOrFail($this->shopId);
        $shop->update(['books_locked_through' => $this->booksLockedThroughInput ?: null]);

        $audit->log(Auth::guard('admin')->user(), 'shop.books_lock_changed', $shop, null, [
            'books_locked_through' => $this->booksLockedThroughInput,
        ]);

        $this->editingBooksLock = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Books lock date updated.']);
    }

    // ── Overview stats ───────────────────────────────────────────────────

    private function overviewStats(int $shopId): object
    {
        return (object) [
            'users'       => User::where('shop_id', $shopId)->count(),
            'products'    => Product::withoutGlobalScopes()->where('shop_id', $shopId)->count(),
            'sales_count' => DB::table('sales')->where('shop_id', $shopId)->where('status', 'confirmed')->count(),
            'sales_total' => (float) DB::table('sales')->where('shop_id', $shopId)->where('status', 'confirmed')->sum('grand_total'),
            'customer_due'=> (float) DB::table('customers')->where('shop_id', $shopId)->sum('current_balance'),
        ];
    }

    private function activityLogs(int $shopId)
    {
        return AdminActionLog::where('shop_id', $shopId)
            ->with('admin')
            ->latest()
            ->limit(20)
            ->get();
    }

    public function render()
    {
        $shop = Shop::withoutGlobalScopes()
            ->with(['owner', 'branches', 'subscriptionPlan', 'activeSubscription.plan'])
            ->findOrFail($this->shopId);

        return view('livewire.admin.shop-detail', [
            'shop'         => $shop,
            'stats'        => $this->overviewStats($this->shopId),
            'activityLogs' => $this->activityLogs($this->shopId),
        ]);
    }
}