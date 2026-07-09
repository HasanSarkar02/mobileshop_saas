<?php

namespace App\Services;

use App\Models\Account;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\DB;

class AccountBalanceChecker
{
    /**
     * Get the current GL balance for a payment account.
     * Balance = sum(debits) - sum(credits) for asset accounts.
     * A positive result means money IS there.
     */
    public function currentBalance(int $paymentAccountId): float
    {
        $pa = PaymentAccount::withoutGlobalScopes()->findOrFail($paymentAccountId);

        $result = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $pa->account_id)
            ->selectRaw('
                COALESCE(SUM(journal_entry_lines.debit), 0)  AS total_debit,
                COALESCE(SUM(journal_entry_lines.credit), 0) AS total_credit
            ')
            ->first();

        return (float) $result->total_debit - (float) $result->total_credit;
    }

    /**
     * Check if a debit of $amount against $paymentAccountId would go negative.
     *
     * Returns:
     *   ['allowed' => true]                        ← OK to proceed
     *   ['allowed' => false, 'message' => '...']   ← Block (cash account)
     *   ['allowed' => true,  'warning' => '...']   ← Warn (bank account)
     */
    public function checkDebit(int $paymentAccountId, float $amount): array
    {
        $pa             = PaymentAccount::withoutGlobalScopes()->findOrFail($paymentAccountId);
        $currentBalance = $this->currentBalance($paymentAccountId);
        $afterDebit     = $currentBalance - $amount;

        if ($afterDebit >= 0) {
            return ['allowed' => true];
        }

        // Negative would result — check account type
        $isCashOrMfs = in_array($pa->provider, [
            'cash', 'bkash', 'nagad', 'rocket', 'upay', 'card',
        ]);

        if ($isCashOrMfs) {
            // Physical accounts — HARD BLOCK
            return [
                'allowed' => false,
                'message' => "Insufficient balance in \"{$pa->name}\". " .
                             "Available: ৳" . number_format($currentBalance, 2) . ", " .
                             "Required: ৳" . number_format($amount, 2) . ". " .
                             "Physical cash and MFS accounts cannot go negative.",
            ];
        }

        // Bank account — overdraft is possible, WARN only
        return [
            'allowed' => true,
            'warning' => "This will overdraft \"{$pa->name}\" by ৳" .
                         number_format(abs($afterDebit), 2) . ". " .
                         "Proceed only if bank overdraft facility is available.",
        ];
    }
}