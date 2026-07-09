<?php

namespace App\Services\Treasury;

use App\Enums\TreasuryTransactionType;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\TreasuryTransaction;
use RuntimeException;

/**
 * Maps every TreasuryTransactionType to the correct double-entry journal lines.
 *
 * GL code reference (aligned with actual ChartOfAccountsProvisioner):
 *   3000  Owner's Equity (capital)          ← already exists in COA
 *   3010  Owner's Drawings                  ← already exists in COA
 *   3020  Opening Balance Equity            ← already exists in COA
 *   3030  Partner Capital                   ← new, provisioned by provisionTreasuryAccounts()
 *   4040  Interest Income                   ← new
 *   4050  Miscellaneous Income (Cash Over)  ← new
 *   6085  Bank Charges & Fees               ← new (6040 = Inventory Shrinkage, different!)
 *   6086  MFS & Payment Gateway Fees        ← new (6050 = Bad Debt, different!)
 *   6087  Interest Expense                  ← new (6060 = FP Settlement Loss, different!)
 *   6088  Cash Short / Misc Loss            ← new
 *   1300  Petty Cash Control                ← new
 *   2100  Short-term Loans Payable          ← new
 */
class TreasuryJournalBuilder
{
    public function build(TreasuryTransaction $transaction): array
    {
        $type   = $transaction->transaction_type;
        $shopId = $transaction->shop_id;
        $amount = (float) $transaction->amount;
        $fee    = (float) $transaction->fee_amount;
        $desc   = $transaction->description;

        $fromGl = $transaction->from_payment_account_id
            ? $this->paymentAccountGl($transaction->from_payment_account_id)
            : null;

        $toGl = $transaction->to_payment_account_id
            ? $this->paymentAccountGl($transaction->to_payment_account_id)
            : null;

        $gl = fn (string $code) => $this->glByCode($shopId, $code);

        return match ($type) {

            // ── A: Internal Transfers ──────────────────────────────────────────
            // Bank deposit / withdrawal / branch-to-branch:
            // fee goes to Bank Charges (6085) if present
            TreasuryTransactionType::AccountTransfer,
            TreasuryTransactionType::BranchTransfer,
            TreasuryTransactionType::BankDeposit,
            TreasuryTransactionType::BankWithdrawal => $this->buildTransfer(
                fromGl: $fromGl,
                toGl:   $toGl,
                amount: $amount,
                fee:    $fee,
                feeGl:  $fee > 0 ? $gl('6085') : null,  // Bank Charges & Fees
                desc:   $desc,
            ),

            // Wallet cashout: fee is MFS fee (6086), not a bank charge
            TreasuryTransactionType::WalletCashout => $this->buildTransfer(
                fromGl: $fromGl,
                toGl:   $toGl,
                amount: $amount,
                fee:    $fee,
                feeGl:  $fee > 0 ? $gl('6086') : null,  // MFS & Payment Gateway Fees
                desc:   $desc,
            ),

            // ── B: Equity & Capital ────────────────────────────────────────────

            // Owner puts money in: Dr Cash/Bank → Cr Owner's Equity (3000)
            TreasuryTransactionType::OwnerCapital => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Capital injection — {$desc}"],
                ['account_id' => $gl('3000')->id,   'credit' => $amount, 'description' => "Owner's Equity"],
            ],

            // Owner takes money out: Dr Owner's Drawings (3010) → Cr Cash/Bank
            TreasuryTransactionType::OwnerDrawings => [
                ['account_id' => $gl('3010')->id,   'debit'  => $amount, 'description' => "Owner's Drawings — {$desc}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Paid from {$transaction->fromAccount?->name}"],
            ],

            // Partner invests: Dr Cash/Bank → Cr Partner Capital (3030)
            TreasuryTransactionType::PartnerInvestment => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Partner investment — {$transaction->third_party_name}"],
                ['account_id' => $gl('3030')->id,   'credit' => $amount, 'description' => "Partner Capital"],
            ],

            // Partner withdraws: Dr Partner Capital (3030) → Cr Cash/Bank
            TreasuryTransactionType::PartnerWithdrawal => [
                ['account_id' => $gl('3030')->id,   'debit'  => $amount, 'description' => "Partner withdrawal — {$transaction->third_party_name}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Paid from {$transaction->fromAccount?->name}"],
            ],

            // ── C: Adjustments ─────────────────────────────────────────────────

            // Physical cash > ledger: Dr Cash → Cr Miscellaneous Income (4050)
            TreasuryTransactionType::CashOver => [
                ['account_id' => $fromGl->id,        'debit'  => $amount, 'description' => "Cash surplus — {$desc}"],
                ['account_id' => $gl('4050')->id,   'credit' => $amount, 'description' => "Miscellaneous Income (Cash Over)"],
            ],

            // Physical cash < ledger: Dr Cash Short/Loss (6088) → Cr Cash
            TreasuryTransactionType::CashShort => [
                ['account_id' => $gl('6088')->id,   'debit'  => $amount, 'description' => "Cash deficit — {$desc}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Cash account adjusted"],
            ],

            // Opening balance: Dr Account → Cr Opening Balance Equity (3020)
            TreasuryTransactionType::OpeningBalance => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Opening balance — {$transaction->toAccount?->name}"],
                ['account_id' => $gl('3020')->id,   'credit' => $amount, 'description' => "Opening Balance Equity"],
            ],

            // ── D: Bank & Finance ──────────────────────────────────────────────

            // Bank deducts maintenance fee: Dr Bank Charges (6085) → Cr Bank
            TreasuryTransactionType::BankCharge => [
                ['account_id' => $gl('6085')->id,   'debit'  => $amount, 'description' => "Bank charge — {$desc}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Deducted from {$transaction->fromAccount?->name}"],
            ],

            // Bank pays interest: Dr Cash/Bank → Cr Interest Income (4040)
            TreasuryTransactionType::InterestIncome => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Interest received — {$desc}"],
                ['account_id' => $gl('4040')->id,   'credit' => $amount, 'description' => "Interest Income"],
            ],

            // Shop pays interest standalone (not part of loan repayment):
            // Dr Interest Expense (6087) → Cr Cash/Bank
            TreasuryTransactionType::InterestExpense => [
                ['account_id' => $gl('6087')->id,   'debit'  => $amount, 'description' => "Interest paid — {$desc}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Paid from {$transaction->fromAccount?->name}"],
            ],

            // Loan received: Dr Cash/Bank → Cr Short-term Loans Payable (2100)
            TreasuryTransactionType::LoanReceipt => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Loan received — {$transaction->third_party_name}"],
                ['account_id' => $gl('2100')->id,   'credit' => $amount, 'description' => "Short-term Loans Payable"],
            ],

            // Loan repayment: amount = principal, fee_amount = interest
            // Dr Loan Payable (principal) + Dr Interest Expense (fee) → Cr Cash/Bank (total)
            TreasuryTransactionType::LoanRepayment => array_values(array_filter([
                ['account_id' => $gl('2100')->id,   'debit'  => $amount,      'description' => "Loan principal — {$transaction->third_party_name}"],
                $fee > 0
                    ? ['account_id' => $gl('6087')->id, 'debit' => $fee,      'description' => "Loan interest — {$transaction->third_party_name}"]
                    : null,
                ['account_id' => $fromGl->id,        'credit' => $amount + $fee, 'description' => "Repaid from {$transaction->fromAccount?->name}"],
            ])),

            // ── E: Petty Cash ──────────────────────────────────────────────────

            // Issue petty cash float: Dr Petty Cash Control (1300) → Cr Cash
            TreasuryTransactionType::PettyCashIssue => [
                ['account_id' => $gl('1300')->id,   'debit'  => $amount, 'description' => "Petty cash issued — {$desc}"],
                ['account_id' => $fromGl->id,        'credit' => $amount, 'description' => "Drawn from {$transaction->fromAccount?->name}"],
            ],

            // Return unused float: Dr Cash → Cr Petty Cash Control (1300)
            TreasuryTransactionType::PettyCashReturn => [
                ['account_id' => $toGl->id,         'debit'  => $amount, 'description' => "Petty cash returned — {$desc}"],
                ['account_id' => $gl('1300')->id,   'credit' => $amount, 'description' => "Petty Cash Control"],
            ],
        };
    }

    /**
     * Build balanced transfer lines.
     * fee = 0: Dr To (full amount), Cr From (full amount)
     * fee > 0: Dr To (net), Dr Fee Account (fee), Cr From (gross)
     */
    private function buildTransfer(
        Account  $fromGl,
        Account  $toGl,
        float    $amount,
        float    $fee,
        ?Account $feeGl,
        string   $desc,
    ): array {
        $net   = $amount - $fee;
        $lines = [];

        // Destination receives NET (what physically arrives)
        $lines[] = [
            'account_id'  => $toGl->id,
            'debit'       => $net,
            'description' => "Transfer received — {$desc}",
        ];

        // Fee debited to expense account if applicable
        if ($fee > 0 && $feeGl) {
            $lines[] = [
                'account_id'  => $feeGl->id,
                'debit'       => $fee,
                'description' => "Transaction fee — {$desc}",
            ];
        }

        // Source account decremented by GROSS (what physically left)
        $lines[] = [
            'account_id'  => $fromGl->id,
            'credit'      => $amount,
            'description' => "Transfer sent — {$desc}",
        ];

        return $lines;
    }

    private function paymentAccountGl(int $paymentAccountId): Account
    {
        $pa = PaymentAccount::withoutGlobalScopes()->findOrFail($paymentAccountId);
        return Account::withoutGlobalScopes()->findOrFail($pa->account_id);
    }

    private function glByCode(int $shopId, string $code): Account
    {
        $account = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new RuntimeException(
                "GL account '{$code}' not found for shop {$shopId}. " .
                "Run: php artisan treasury:provision-gl-accounts"
            );
        }

        return $account;
    }

    /**
     * Verify treasury-specific GL accounts exist before posting any journal.
     * Accounts that are in DEFAULT_ACCOUNTS (3000, 3010, 3020) are always
     * present, so only check the new treasury-specific ones.
     */
    public function validateGlAccounts(int $shopId): void
    {
        // Only the accounts added by provisionTreasuryAccounts() — not the
        // base COA accounts that every shop already has.
        $treasuryOnly = [
            '1300' => 'Petty Cash Control',
            '2100' => 'Short-term Loans Payable',
            '2200' => 'Long-term Loans Payable',
            '3030' => 'Partner Capital',
            '4040' => 'Interest Income',
            '4050' => 'Miscellaneous Income',
            '6085' => 'Bank Charges & Fees',
            '6086' => 'MFS & Payment Gateway Fees',
            '6087' => 'Interest Expense',
            '6088' => 'Cash Short / Miscellaneous Loss',
        ];

        $existingCodes = Account::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->whereIn('code', array_keys($treasuryOnly))
            ->pluck('code')
            ->toArray();

        $missing = array_diff(array_keys($treasuryOnly), $existingCodes);

        if (! empty($missing)) {
            $names = implode(', ', array_map(fn ($c) => "{$c} ({$treasuryOnly[$c]})", $missing));
            throw new RuntimeException(
                "Missing treasury GL accounts: {$names}. " .
                "Run: php artisan treasury:provision-gl-accounts"
            );
        }
    }
}