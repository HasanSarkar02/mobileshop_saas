<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\Shop;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Settings')]
class ShopSettings extends Component
{
    use WithFileUploads;
    // ── Active Tab ─────────────────────────────────────────────────────────────
    public string $activeTab = 'profile';

    public $shopLogo = null;  // temp upload
    public string $tradeLicenseNumber = '';
    public string $website            = '';
    public string $documentFooterNote = '';
    public bool   $showConfidential   = false;

    // ── SMS Settings ──────────────────────────────────────────────────────────
    public bool   $smsEnabled       = false;
    public string $smsProvider      = 'bulk_sms_bd';
    public string $smsApiKey        = '';
    public string $smsSenderId      = '';
    public bool   $smsOnSale        = false;
    public bool   $smsOnDueReminder = false;
    public bool   $smsOnServiceReady= false;

    // ── Shop Profile ───────────────────────────────────────────────────────────
    #[Validate('required|string|max:255')]
    public string $shopName = '';

    #[Validate('nullable|string|max:20')]
    public string $shopPhone = '';

    #[Validate('nullable|email|max:255')]
    public string $shopEmail = '';

    #[Validate('nullable|string|max:500')]
    public string $shopAddress = '';

    #[Validate('required|string|max:50')]
    public string $timezone = 'Asia/Dhaka';

    #[Validate('required|string|max:3')]
    public string $currency = 'BDT';

    // ── VAT Settings ───────────────────────────────────────────────────────────
    public bool $vatEnabled = false;

    #[Validate('nullable|string|max:100')]
    public string $vatRegistrationNumber = '';

    #[Validate('nullable|numeric|min:0|max:100')]
    public string $defaultVatRate = '0';

    // ── Payment Account Form ───────────────────────────────────────────────────
    public bool $showPaymentForm = false;

    #[Validate('required|string|max:100')]
    public string $paymentName = '';

    #[Validate('required|in:cash,bank,bkash,nagad,rocket,upay,card,other')]
    public string $paymentProvider = 'bank';

    #[Validate('nullable|string|max:50')]
    public string $paymentAccountNumber = '';

    #[Validate('nullable|string|max:100')]
    public string $paymentBankName = '';

    public ?int $editingPaymentId = null;
    public ?int $paymentBranchId = null;

    // ── Branch Form ───────────────────────────────────────────────────────────
    public bool $showBranchForm = false;

    #[Validate('required|string|max:255')]
    public string $branchName = '';

    #[Validate('required|string|max:20')]
    public string $branchCode = '';

    #[Validate('nullable|string|max:500')]
    public string $branchAddress = '';

    #[Validate('nullable|string|max:20')]
    public string $branchPhone = '';

    public ?int $editingBranchId = null;

    // ── Business Rules ────────────────────────────────────────────────────────
    public string $expenseApprovalThreshold = '0';

    //Treasury approval threshold and petty cash limit 
    public string $treasuryApprovalThreshold = '10000';
    public string $pettyCashLimit            = '5000';

    public function mount(): void
    {
        $shop = $this->shop;
        $this->shopName = $shop->name;
        $this->shopPhone = $shop->phone ?? '';
        $this->shopEmail = $shop->email ?? '';
        $this->shopAddress = $shop->address ?? '';
        $this->timezone = $shop->timezone;
        $this->currency = $shop->currency;
        $this->vatEnabled = $shop->vat_enabled;
        $this->vatRegistrationNumber = $shop->vat_registration_number ?? '';
        $this->defaultVatRate = (string) $shop->default_vat_rate;
        $this->expenseApprovalThreshold = (string) $shop->expense_approval_threshold;
        $this->tradeLicenseNumber = $shop->trade_license_number ?? '';
        $this->website            = $shop->website ?? '';
        $this->documentFooterNote = $shop->document_footer_note ?? '';
        $this->showConfidential   = (bool) $shop->show_document_confidential;
        $this->smsEnabled        = (bool) $shop->sms_enabled;
        $this->smsProvider       = $shop->sms_provider ?? 'bulk_sms_bd';
        $this->smsApiKey         = $shop->sms_api_key ?? '';
        $this->smsSenderId       = $shop->sms_sender_id ?? '';
        $this->smsOnSale         = (bool) $shop->sms_on_sale;
        $this->smsOnDueReminder  = (bool) $shop->sms_on_due_reminder;
        $this->smsOnServiceReady = (bool) $shop->sms_on_service_ready;
        $this->treasuryApprovalThreshold = (string) ($shop->treasury_approval_threshold ?? 10000);
        $this->pettyCashLimit            = (string) ($shop->petty_cash_limit ?? 5000);
    }

    // ── Computed Properties ────────────────────

    #[Computed]
    public function shop(): Shop
    {
        return Auth::user()->shop()->firstOrFail();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::with('account', 'branch')
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::orderByDesc('is_main')->orderBy('name')->get();
    }

    // ── Profile Tab ───────────────────────────────────────────────────────────

    public function saveProfile(): void
    {
        $this->validateOnly('shopName,shopPhone,shopEmail,shopAddress,timezone,currency');

        $updates = [
            'name'                     => $this->shopName,
            'phone'                    => $this->shopPhone ?: null,
            'email'                    => $this->shopEmail ?: null,
            'address'                  => $this->shopAddress ?: null,
            'timezone'                 => $this->timezone,
            'currency'                 => $this->currency,
            'trade_license_number'     => $this->tradeLicenseNumber ?: null,
            'website'                  => $this->website ?: null,
            'document_footer_note'     => $this->documentFooterNote ?: null,
            'show_document_confidential' => $this->showConfidential,
        ];

        if ($this->shopLogo) {
            $this->validate(['shopLogo' => 'image|max:2048|mimes:jpg,jpeg,png,webp']);
            $updates['logo_path'] = $this->shopLogo->store(
                "shops/{$this->shop->id}/branding", 'public'
            );
        }

        $this->shop->update($updates);
        unset($this->shop);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Shop profile updated.']);
    }

    public function saveVat(): void
    {
        $this->validateOnly('vatRegistrationNumber,defaultVatRate');

        $this->shop->update([
            'vat_enabled' => $this->vatEnabled,
            'vat_registration_number' => $this->vatEnabled ? ($this->vatRegistrationNumber ?: null) : null,
            'default_vat_rate' => $this->vatEnabled ? (float) $this->defaultVatRate : 0,
        ]);

        unset($this->shop);
        $this->dispatch('notify', type: 'success', message: 'VAT settings saved.');
    }

    // ── Payment Accounts Tab ──────────────────────────────────────────────────

    public function openPaymentForm(?int $paymentId = null): void
    {
        $this->resetPaymentForm();
        $this->showPaymentForm = true;
        $this->editingPaymentId = $paymentId;

        if ($paymentId) {
            $p = PaymentAccount::findOrFail($paymentId);
            $this->paymentName = $p->name;
            $this->paymentProvider = $p->provider;
            $this->paymentAccountNumber = $p->account_number ?? '';
            $this->paymentBankName = $p->bank_name ?? '';
            $this->paymentBranchId = $p->branch_id;
        }
    }

    public function savePaymentAccount(ChartOfAccountsProvisioner $provisioner): void
    {
        $this->validateOnly('paymentName,paymentProvider,paymentAccountNumber,paymentBankName');

        DB::transaction(function () use ($provisioner) {
            $shop = $this->shop;

            if ($this->editingPaymentId) {
                // Update existing — just update the display fields, not the GL account name
                PaymentAccount::findOrFail($this->editingPaymentId)->update([
                    'name' => $this->paymentName,
                    'account_number' => $this->paymentAccountNumber ?: null,
                    'bank_name' => $this->paymentBankName ?: null,
                ]);
            } else {
                // New — creates both a GL account AND a PaymentAccount row
                if ($this->paymentProvider === 'cash') {
                    $branch = $this->paymentBranchId
                        ? Branch::findOrFail($this->paymentBranchId)
                        : Branch::where('shop_id', $shop->id)->where('is_main', true)->firstOrFail();

                    $provisioner->provisionCashAccountForBranch($shop, $branch);
                } else {
                    $provisioner->provisionCustomPaymentAccount(
                        shop: $shop,
                        name: $this->paymentName,
                        provider: $this->paymentProvider,
                        accountNumber: $this->paymentAccountNumber ?: null,
                        bankName: $this->paymentBankName ?: null,
                        branchId: $this->paymentBranchId ?: null,
                    );
                }
            }
        });

        unset($this->paymentAccounts);
        $this->resetPaymentForm();
        $this->dispatch('notify', type: 'success', message: 'Payment account saved.');
    }

    public function deactivatePaymentAccount(int $id): void
    {
        $payment = PaymentAccount::findOrFail($id);

        // Check if GL account has recent transactions
        $hasTransactions = Account::where('id', $payment->account_id)
            ->whereHas('lines')
            ->exists();

        if ($hasTransactions) {
            // Cannot delete — deactivate both payment account and GL account
            $payment->update(['is_active' => false]);
            Account::where('id', $payment->account_id)->update(['is_active' => false]);
        } else {
            // No transactions — safe to hard delete both
            DB::transaction(function () use ($payment) {
                Account::find($payment->account_id)?->delete();
                $payment->delete();
            });
        }

        unset($this->paymentAccounts);
        $this->dispatch('notify', type: 'success', message: 'Payment account removed.');
    }

    private function resetPaymentForm(): void
    {
        $this->showPaymentForm = false;
        $this->editingPaymentId = null;
        $this->paymentName = '';
        $this->paymentProvider = 'bank';
        $this->paymentAccountNumber = '';
        $this->paymentBankName = '';
        $this->paymentBranchId = null;
        $this->resetErrorBag(['paymentName', 'paymentProvider', 'paymentAccountNumber', 'paymentBankName']);
    }

    // ── Branches Tab ──────────────────────────────────────────────────────────

    public function openBranchForm(?int $branchId = null): void
    {
        $this->resetBranchForm();
        $this->showBranchForm = true;
        $this->editingBranchId = $branchId;

        if ($branchId) {
            $b = Branch::findOrFail($branchId);
            $this->branchName = $b->name;
            $this->branchCode = $b->code;
            $this->branchAddress = $b->address ?? '';
            $this->branchPhone = $b->phone ?? '';
        }
    }

    public function saveBranch(ChartOfAccountsProvisioner $provisioner): void
    {
        $this->validateOnly('branchName,branchCode,branchAddress,branchPhone');

        DB::transaction(function () use ($provisioner) {
            $shop = $this->shop;

            if ($this->editingBranchId) {
                $branch = Branch::findOrFail($this->editingBranchId);

                // Prevent changing code if branch has transactions
                $hasActivity = $branch->units()->exists() || $branch->stock()->exists();
                $codeChanged = strtoupper($this->branchCode) !== $branch->code;

                if ($hasActivity && $codeChanged) {
                    $this->addError('branchCode', 'Cannot change branch code after stock has been received.');
                    return;
                }

                $branch->update([
                    'name' => $this->branchName,
                    'code' => strtoupper($this->branchCode),
                    'address' => $this->branchAddress ?: null,
                    'phone' => $this->branchPhone ?: null,
                ]);
            } else {
                // New branch — auto-provision Cash account and Payment Account for it
                $branch = Branch::create([
                    'shop_id' => $shop->id,
                    'name' => $this->branchName,
                    'code' => strtoupper($this->branchCode),
                    'address' => $this->branchAddress ?: null,
                    'phone' => $this->branchPhone ?: null,
                    'is_main' => false,
                    'is_active' => true,
                ]);

                $provisioner->provisionCashAccountForBranch($shop, $branch);
            }
        });

        unset($this->branches);
        $this->resetBranchForm();
        $this->dispatch('notify', type: 'success', message: 'Branch saved.');
    }

    public function toggleBranch(int $branchId): void
    {
        $branch = Branch::findOrFail($branchId);

        if ($branch->is_main) {
            $this->dispatch('notify', type: 'error', message: 'The main branch cannot be deactivated.');
            return;
        }

        $branch->update(['is_active' => ! $branch->is_active]);
        unset($this->branches);
        $this->dispatch('notify', type: 'success', message: 'Branch status updated.');
    }

    private function resetBranchForm(): void
    {
        $this->showBranchForm = false;
        $this->editingBranchId = null;
        $this->branchName = '';
        $this->branchCode = '';
        $this->branchAddress = '';
        $this->branchPhone = '';
        $this->resetErrorBag(['branchName', 'branchCode', 'branchAddress', 'branchPhone']);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.settings.shop-settings');
    }

    // ── Finance Partners ──────────────────────────────────────────────────────
    public bool   $showFpForm    = false;
    public ?int   $editingFpId   = null;
    public string $fpName        = '';
    public string $fpPhone       = '';
    public string $fpContactPerson = '';
    public string $fpFeePercent  = '0';
    public string $fpNotes       = '';

    #[Computed]
    public function financePartners(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\FinancePartner::orderBy('name')->get();
    }

    public function openFpForm(?int $id = null): void
    {
        $this->showFpForm = true;
        $this->editingFpId = $id;
        if ($id) {
            $fp = \App\Models\FinancePartner::findOrFail($id);
            $this->fpName = $fp->name;
            $this->fpPhone = $fp->phone ?? '';
            $this->fpContactPerson = $fp->contact_person ?? '';
            $this->fpFeePercent = (string) $fp->processing_fee_percent;
            $this->fpNotes = $fp->notes ?? '';
        } else {
            $this->fpName = $this->fpPhone = $this->fpContactPerson = $this->fpNotes = '';
            $this->fpFeePercent = '0';
        }
    }

    public function saveFinancePartner(): void
    {
        $this->validateOnly('fpName', ['fpName' => 'required|string|max:255']);

        $data = [
            'shop_id'                => Auth::user()->shop_id,
            'name'                   => $this->fpName,
            'phone'                  => $this->fpPhone ?: null,
            'contact_person'         => $this->fpContactPerson ?: null,
            'processing_fee_percent' => (float) $this->fpFeePercent,
            'notes'                  => $this->fpNotes ?: null,
            'is_active'              => true,
        ];

        if ($this->editingFpId) {
            \App\Models\FinancePartner::findOrFail($this->editingFpId)->update($data);
        } else {
            \App\Models\FinancePartner::create($data);
        }

        unset($this->financePartners);
        $this->showFpForm = false;
        $this->dispatch('notify', type: 'success', message: 'Finance partner saved.');
    }

    public function toggleFpStatus(int $id): void
    {
        $fp = \App\Models\FinancePartner::findOrFail($id);
        $fp->update(['is_active' => ! $fp->is_active]);
        unset($this->financePartners);
        $this->dispatch('notify', type: 'success', message: 'Updated.');
    }

    public function saveBusinessRules(): void
    {
        $this->validateOnly('expenseApprovalThreshold',
            ['expenseApprovalThreshold' => 'required|numeric|min:0']);

        $this->shop->update([
            'expense_approval_threshold' => (float) $this->expenseApprovalThreshold,
        ]);
        unset($this->shop);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Business rules saved.']);

        $this->shop->update([
            'expense_approval_threshold'  => (float) $this->expenseApprovalThreshold,
            'treasury_approval_threshold' => (float) $this->treasuryApprovalThreshold,
            'petty_cash_limit'            => (float) $this->pettyCashLimit,
        ]);
    }

    public function saveSmsSettings(): void
    {
        $this->shop->update([
            'sms_enabled'         => $this->smsEnabled,
            'sms_provider'        => $this->smsProvider,
            'sms_api_key'         => $this->smsApiKey ?: null,
            'sms_sender_id'       => $this->smsSenderId ?: null,
            'sms_on_sale'         => $this->smsOnSale,
            'sms_on_due_reminder' => $this->smsOnDueReminder,
            'sms_on_service_ready'=> $this->smsOnServiceReady,
        ]);
        unset($this->shop);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'SMS settings saved.']);
    }

    public function testSms(): void
    {
        $shop  = $this->shop;
        $phone = auth()->user()->phone;

        if (! $phone) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Your user account has no phone number to test with.']);
            return;
        }

        $result = app(\App\Services\SmsService::class)->send(
            shop:      $shop,
            to:        $phone,
            message:   "{$shop->name}: SMS test successful. Sent at " . now()->format('H:i d M Y') . ".",
            template:  'test',
            createdBy: auth()->id(),
        );

        $this->dispatch('notify', [
            'type'    => $result ? 'success' : 'error',
            'message' => $result ? "Test SMS sent to {$phone}." : 'SMS failed. Check your API key.',
        ]);
    }
}