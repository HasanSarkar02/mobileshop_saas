<?php
namespace App\Actions;

use App\Enums\SalaryDrawType;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\SalaryDraw;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class RecordSalaryDrawAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function execute(Shop $shop, User $employee, array $data, User $actor): SalaryDraw
    {
        return DB::transaction(function () use ($shop, $employee, $data, $actor) {

            $draw = SalaryDraw::create([
                'shop_id'            => $shop->id,
                'user_id'            => $employee->id,
                'amount'             => (float) $data['amount'],
                'payment_account_id' => $data['payment_account_id'],
                'draw_date'          => $data['draw_date'],
                'for_year'           => (int) $data['for_year'],
                'for_month'          => (int) $data['for_month'],
                'draw_type'          => $data['draw_type'] ?? SalaryDrawType::Salary->value,
                'notes'              => $data['notes'] ?? null,
                'created_by'         => $actor->id,
            ]);

            // Journal: Dr Salary Expense / Cr Payment Account
            $salaryAcc = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '6020')->firstOrFail();
            $pa   = PaymentAccount::withoutGlobalScopes()->findOrFail($data['payment_account_id']);
            $glPa = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

            $this->accounting->postEntry(
                shop: $shop,
                description: "Salary draw — {$employee->name} ({$data['for_year']}-{$data['for_month']})",
                lines: [
                    ['account_id' => $salaryAcc->id, 'debit'  => $draw->amount,
                     'description' => "Salary draw — {$employee->name}"],
                    ['account_id' => $glPa->id,      'credit' => $draw->amount,
                     'description' => "Paid via {$pa->name}"],
                ],
                reference: $draw,
                actor: $actor,
            );

            return $draw;
        });
    }
}