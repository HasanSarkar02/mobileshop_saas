<?php

namespace App\Actions;

use App\Enums\AdvanceStatus;
use App\Enums\PayrollStatus;
use App\Enums\SalaryDrawType;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\SalaryAdvance;
use App\Models\SalaryDraw;
use App\Models\Shop;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessPayrollAction
{
    public function __construct(private readonly AccountingService $accounting) {}

    /**
     * Generate a draft payroll run for the given month.
     * All employees with profiles are included.
     */
    public function generateDraft(Shop $shop, int $year, int $month, User $actor): PayrollRun
    {
        $exists = PayrollRun::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($exists) {
            throw new RuntimeException("Payroll for this month already exists. Delete the draft first to regenerate.");
        }

        return DB::transaction(function () use ($shop, $year, $month, $actor) {
            $run = PayrollRun::create([
                'shop_id'    => $shop->id,
                'year'       => $year,
                'month'      => $month,
                'status'     => PayrollStatus::Draft,
                'created_by' => $actor->id,
            ]);

            // Include ALL employees — even those without system access
            $employees = User::withoutGlobalScopes()
                ->where('shop_id', $shop->id)
                ->where('user_type', 'employee')
                ->where('is_active', true)
                ->with(['employeeProfile.salaryPaymentAccount'])
                ->get();

            $totalGross = 0.0;
            $totalDed   = 0.0;
            $totalNet   = 0.0;

            foreach ($employees as $employee) {
                $profile = $employee->employeeProfile;
                if (! $profile || $profile->base_salary <= 0) continue;

                $gross = $profile->grossSalary();

                // Sum all salary draws for this month (salary type)
                $alreadyDrawn = SalaryDraw::withoutGlobalScopes()
                    ->where('user_id', $employee->id)
                    ->where('for_year', $year)
                    ->where('for_month', $month)
                    ->whereNull('payroll_item_id') // not yet settled
                    ->sum('amount');

                // Previous month overdraw deduction
                $overdrawDeduction = (float) SalaryDraw::withoutGlobalScopes()
                    ->where('user_id', $employee->id)
                    ->where('draw_type', SalaryDrawType::Advance->value)
                    ->whereNull('payroll_item_id')
                    ->sum('amount');

                $netRemaining = $gross - $alreadyDrawn - $overdrawDeduction;

                // If negative: employee overdrawn this month
                $settleAmount = max(0, $netRemaining);
                $overdraw     = abs(min(0, $netRemaining));

                $item = PayrollItem::create([
                    'payroll_run_id'     => $run->id,
                    'user_id'            => $employee->id,
                    'shop_id'            => $shop->id,
                    'base_salary'        => $profile->base_salary,
                    'house_allowance'    => $profile->house_allowance,
                    'transport_allowance'=> $profile->transport_allowance,
                    'other_allowance'    => $profile->other_allowance,
                    'gross_salary'       => $gross,
                    'bonus'              => 0,
                    'advance_deduction'  => $alreadyDrawn + $overdrawDeduction,
                    'other_deduction'    => $overdraw, // overdraw stored here
                    'total_deductions'   => $alreadyDrawn + $overdrawDeduction,
                    'net_salary'         => $settleAmount,
                    'payment_account_id' => $profile->salary_payment_account_id,
                    'notes'              => $alreadyDrawn > 0
                        ? "Already drawn: ৳{$alreadyDrawn}" . ($overdraw > 0 ? " | Overdraw: ৳{$overdraw}" : '')
                        : null,
                    'is_paid'            => false,
                ]);

                // Link draws to this payroll item
                SalaryDraw::withoutGlobalScopes()
                    ->where('user_id', $employee->id)
                    ->where('for_year', $year)
                    ->where('for_month', $month)
                    ->whereNull('payroll_item_id')
                    ->update(['payroll_item_id' => $item->id]);

                $totalGross += $gross;
                $totalDed   += ($alreadyDrawn + $overdrawDeduction);
                $totalNet   += $settleAmount;
            }

            $run->update([
                'total_gross'      => $totalGross,
                'total_deductions' => $totalDed,
                'total_net'        => $totalNet,
            ]);

            return $run->fresh('items.user');
        });
    }

    public function deleteDraft(PayrollRun $run): void
    {
        if ($run->status !== PayrollStatus::Draft) {
            throw new RuntimeException("Only draft payrolls can be deleted.");
        }

        DB::transaction(function () use ($run) {
            // Unlink draws from this payroll
            SalaryDraw::withoutGlobalScopes()
                ->whereIn('payroll_item_id', $run->items->pluck('id'))
                ->update(['payroll_item_id' => null]);

            $run->items()->delete();
            $run->delete();
        });
    }

    /**
     * Recalculate run totals after items are edited.
     */
    public function recalculateTotals(PayrollRun $run): void
    {
        $run->load('items');

        $totalGross = $run->items->sum('gross_salary') + $run->items->sum('bonus');
        $totalDed   = $run->items->sum('total_deductions');

        $run->update([
            'total_gross'      => $totalGross,
            'total_deductions' => $totalDed,
            'total_net'        => $totalGross - $totalDed,
        ]);
    }

    /**
     * Mark payroll as approved (no journal entry yet — posted on payment).
     */
    public function approve(PayrollRun $run, User $actor): PayrollRun
    {
        if ($run->status !== PayrollStatus::Draft) {
            throw new RuntimeException("Only draft payrolls can be approved.");
        }

        $run->update([
            'status'      => PayrollStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        return $run->fresh();
    }

    /**
     * Pay the payroll — creates journal entries and marks employees as paid.
     * Two entries per employee:
     *   Dr Salary Expense / Cr Salary Payable (accrual)
     *   Dr Salary Payable  / Cr Cash/Bank      (payment)
     * Combined into one balanced entry: Dr Salary Expense / Cr Cash/Bank
     */
    public function pay(PayrollRun $run, int $defaultPaymentAccountId, User $actor): PayrollRun
    {
        if ($run->status !== PayrollStatus::Approved) {
            throw new RuntimeException("Payroll must be approved before payment.");
        }

        return DB::transaction(function () use ($run, $defaultPaymentAccountId, $actor) {
            $shop = $run->shop()->withoutGlobalScopes()->findOrFail($run->shop_id);
            $run->load('items');

            $salaryExpenseAccount = Account::withoutGlobalScopes()
                ->where('shop_id', $shop->id)->where('code', '6020')->firstOrFail();

            $journalLines = [];
            $totalNet     = 0.0;

            foreach ($run->items as $item) {
                $net = (float) $item->net_salary;
                if ($net <= 0) continue;

                // Each item can have its own payment account
                $paId = $item->payment_account_id ?? $defaultPaymentAccountId;
                $pa   = PaymentAccount::withoutGlobalScopes()->findOrFail($paId);
                $glPa = Account::withoutGlobalScopes()->findOrFail($pa->account_id);

                // Group by payment account to minimize journal lines
                $key = $glPa->id;
                if (! isset($journalLines[$key])) {
                    $journalLines[$key] = [
                        'account_id'  => $glPa->id,
                        'credit'      => 0,
                        'description' => "Salary paid via {$pa->name}",
                    ];
                }
                $journalLines[$key]['credit'] += $net;
                $totalNet += $net;

                // Handle advance deduction
                if ($item->advance_id && $item->advance_deduction > 0) {
                    $advance = SalaryAdvance::withoutGlobalScopes()->find($item->advance_id);
                    if ($advance) {
                        $newBalance = max(0, (float) $advance->balance_remaining - (float) $item->advance_deduction);
                        $advance->update([
                            'balance_remaining' => $newBalance,
                            'status'            => $newBalance <= 0
                                ? AdvanceStatus::FullyPaid->value
                                : AdvanceStatus::Active->value,
                        ]);
                    }
                }

                $item->update(['is_paid' => true]);
            }

            if ($totalNet <= 0) {
                throw new RuntimeException('No payable salary amounts found in this payroll run.');
            }

            // Salary expense debit line
            $allLines = array_merge(
                [['account_id' => $salaryExpenseAccount->id, 'debit' => $totalNet, 'description' => "Salary — {$run->monthName()}"]],
                array_values($journalLines)
            );

            $this->accounting->postEntry(
                shop: $shop,
                description: "Payroll {$run->monthName()} — {$run->items->count()} employees",
                lines: $allLines,
                reference: $run,
                actor: $actor,
            );

            $run->update(['status' => PayrollStatus::Paid, 'paid_at' => now()]);

            return $run->fresh();
        });
    }
}