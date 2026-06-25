<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\SalaryAdvance;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class GiveAdvanceAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(Shop $shop, User $employee, array $data, User $actor): SalaryAdvance
    {
        return DB::transaction(function () use ($shop, $employee, $data, $actor) {
            $advance = SalaryAdvance::create([
                'shop_id'           => $shop->id,
                'user_id'           => $employee->id,
                'amount'            => $data['amount'],
                'balance_remaining' => $data['amount'],
                'monthly_deduction' => $data['monthly_deduction'] ?? 0,
                'advance_date'      => $data['advance_date'],
                'purpose'           => $data['purpose'] ?? null,
                'payment_account_id'=> $data['payment_account_id'],
                'created_by'        => $actor->id,
            ]);

            // Journal: Dr Salary Advance (using miscellaneous expense as placeholder)
            // or we can use a dedicated Advance GL. Using 6020 Salary Expense directly.
            $salaryAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '6020')->firstOrFail();
            $pa = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $glPa = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $this->accounting->postEntry(
                shop: $shop,
                description: "Salary advance — {$employee->name}",
                lines: [
                    ['account_id' => $salaryAcc->id, 'debit'  => $data['amount'], 'description' => "Advance to {$employee->name}"],
                    ['account_id' => $glPa->id,      'credit' => $data['amount'], 'description' => "Paid via {$pa->name}"],
                ],
                reference: $advance,
                actor: $actor,
            );

            return $advance;
        });
    }
}