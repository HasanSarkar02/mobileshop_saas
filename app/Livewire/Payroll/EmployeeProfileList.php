<?php

namespace App\Livewire\Payroll;

use App\Actions\GiveAdvanceAction;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\EmployeeProfile;
use App\Models\PaymentAccount;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Employee Salaries')]
class EmployeeProfileList extends Component
{
    // Inline edit
    public bool $showForm   = false;
    public ?int $editUserId = null;

    public int    $userId              = 0;
    public string $designation         = '';
    public string $baseSalary          = '';
    public string $houseAllowance      = '0';
    public string $transportAllowance  = '0';
    public string $otherAllowance      = '0';
    public string $joiningDate         = '';
    public string $nidNumber           = '';
    public int    $salaryPayAccId      = 0;

    // Advance form
    public bool   $showAdvanceForm   = false;
    public ?int   $advanceUserId     = null;
    public string $advanceAmount     = '';
    public string $monthlyDeduction  = '0';
    public string $advanceDate       = '';
    public string $advancePurpose    = '';
    public int    $advancePayAccId   = 0;

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('user_type', UserType::Employee->value)
            ->where('is_active', true)
            ->with(['employeeProfile', 'branch'])
            ->get();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    public function openForm(int $userId): void
    {
        $this->editUserId = $userId;
        $this->showForm   = true;

        $profile = EmployeeProfile::where('user_id', $userId)->first();

        if ($profile) {
            $this->designation        = $profile->designation ?? '';
            $this->baseSalary         = (string) $profile->base_salary;
            $this->houseAllowance     = (string) $profile->house_allowance;
            $this->transportAllowance = (string) $profile->transport_allowance;
            $this->otherAllowance     = (string) $profile->other_allowance;
            $this->joiningDate        = $profile->joining_date?->format('Y-m-d') ?? '';
            $this->nidNumber          = $profile->nid_number ?? '';
            $this->salaryPayAccId     = $profile->salary_payment_account_id ?? 0;
        } else {
            $this->designation = $this->baseSalary = $this->nidNumber = $this->joiningDate = '';
            $this->houseAllowance = $this->transportAllowance = $this->otherAllowance = '0';
            $this->salaryPayAccId = 0;
        }
    }

    public function saveProfile(): void
    {
        $this->validate([
            'baseSalary' => 'required|numeric|min:0',
        ]);

        $shopId = Auth::user()->shop_id;

        EmployeeProfile::updateOrCreate(
            ['user_id' => $this->editUserId],
            [
                'shop_id'                    => $shopId,
                'designation'                => $this->designation ?: null,
                'base_salary'                => (float) $this->baseSalary,
                'house_allowance'            => (float) $this->houseAllowance,
                'transport_allowance'        => (float) $this->transportAllowance,
                'other_allowance'            => (float) $this->otherAllowance,
                'joining_date'               => $this->joiningDate ?: null,
                'nid_number'                 => $this->nidNumber ?: null,
                'salary_payment_account_id'  => $this->salaryPayAccId ?: null,
            ]
        );

        unset($this->employees);
        $this->showForm = false;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Salary profile saved.']);
    }

    // ── New: create payroll-only local employee (no account) ──────────────────
    public bool   $showLocalEmpForm = false;
    public string $localEmpName     = '';
    public string $localEmpPhone    = '';

    public function createLocalEmployee(): void
    {
        $this->validate([
            'localEmpName'  => 'required|string|max:255',
            'localEmpPhone' => 'nullable|string|max:20',
        ]);

        $shopId  = Auth::user()->shop_id;
        $unique  = \Illuminate\Support\Str::random(8) . '@local.noaccount';

        User::create([
            'shop_id'            => $shopId,
            'user_type'          => \App\Enums\UserType::Employee->value,
            'name'               => $this->localEmpName,
            'email'              => $unique,
            'password'           => \Illuminate\Support\Str::password(40),
            'phone'              => $this->localEmpPhone ?: null,
            'is_active'          => true,
            'has_system_access'  => false, // cannot login
            'email_verified_at'  => now(),
        ]);

        unset($this->employees);
        $this->showLocalEmpForm = false;
        $this->localEmpName     = '';
        $this->localEmpPhone    = '';
        $this->dispatch('notify', ['type' => 'success',
            'message' => "Local employee \"{$this->localEmpName}\" added (payroll only)."]);
    }

    public function openAdvanceForm(int $userId): void
    {
        $this->advanceUserId    = $userId;
        $this->showAdvanceForm  = true;
        $this->advanceDate      = now()->format('Y-m-d');
        $this->advanceAmount    = $this->advancePurpose = '';
        $this->monthlyDeduction = '0';
        $this->advancePayAccId  = 0;
    }

    public function giveAdvance(GiveAdvanceAction $action): void
    {
        $this->validate([
            'advanceAmount'   => 'required|numeric|min:1',
            'advanceDate'     => 'required|date',
            'advancePayAccId' => 'required|integer|min:1',
        ], ['advancePayAccId.min' => 'Select a payment account.']);

        $shop     = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $employee = User::withoutGlobalScopes()->findOrFail($this->advanceUserId);

        try {
            $action->execute($shop, $employee, [
                'amount'            => (float) $this->advanceAmount,
                'monthly_deduction' => (float) $this->monthlyDeduction,
                'advance_date'      => $this->advanceDate,
                'purpose'           => $this->advancePurpose ?: null,
                'payment_account_id'=> $this->advancePayAccId,
            ], Auth::user());

            $this->showAdvanceForm = false;
            $this->dispatch('notify', ['type' => 'success', 'message' => "Advance of ৳{$this->advanceAmount} given."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.employee-profile-list');
    }
}