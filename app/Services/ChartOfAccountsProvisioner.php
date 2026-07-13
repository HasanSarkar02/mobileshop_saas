<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\Shop;

class ChartOfAccountsProvisioner
{
    /**
     * Default chart of accounts for every new shop. Standard numbering:
     * 1000s assets, 2000s liabilities, 3000s equity, 4000s revenue,
     * 5000s cost of sales, 6000s operating expenses.
     */
    private const DEFAULT_ACCOUNTS = [
        ['code' => '1000', 'name' => 'Cash & Bank', 'type' => AccountType::Asset, 'is_header' => true],
        ['code' => '1100', 'name' => 'Accounts Receivable - Customers', 'type' => AccountType::Asset],
        ['code' => '1110', 'name' => 'Accounts Receivable - Finance Partners', 'type' => AccountType::Asset],
        ['code' => '1120', 'name' => 'VAT Receivable (Input VAT)', 'type' => AccountType::Asset],
        ['code' => '1200', 'name' => 'Inventory Asset', 'type' => AccountType::Asset],
        ['code' => '1300', 'name' => 'Petty Cash Control', 'type' => AccountType::Asset],

        ['code' => '2000', 'name' => 'Accounts Payable - Suppliers', 'type' => AccountType::Liability],
        ['code' => '2010', 'name' => 'VAT Payable (Output VAT)', 'type' => AccountType::Liability],
        ['code' => '2020', 'name' => 'Customer Advances / Deposits', 'type' => AccountType::Liability],
        ['code' => '2030', 'name' => 'Salary Payable', 'type' => AccountType::Liability],
        ['code' => '2040', 'name' => 'Due to Finance Partners', 'type' => AccountType::Liability],
        ['code' => '2100', 'name' => 'Short-term Loans Payable', 'type' => AccountType::Liability],
        ['code' => '2200', 'name' => 'Long-term Loans Payable', 'type' => AccountType::Liability],

        ['code' => '3000', 'name' => "Owner's Equity", 'type' => AccountType::Equity],
        ['code' => '3010', 'name' => "Owner's Drawings", 'type' => AccountType::Equity],
        ['code' => '3020', 'name' => 'Opening Balance Equity', 'type' => AccountType::Equity],
         ['code' => '3030', 'name' => 'Partner Capital', 'type' => AccountType::Equity],

        ['code' => '4000', 'name' => 'Sales Revenue', 'type' => AccountType::Revenue],
        ['code' => '4010', 'name' => 'Sales Returns & Allowances', 'type' => AccountType::Revenue],
        ['code' => '4020', 'name' => 'Sales Discounts', 'type' => AccountType::Revenue],
        ['code' => '4030', 'name' => 'Service Revenue', 'type' => AccountType::Revenue],
        ['code' => '4040', 'name' => 'Interest Income', 'type' => AccountType::Revenue],
        ['code' => '4050', 'name' => 'Miscellaneous Income', 'type' => AccountType::Revenue],

        ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => AccountType::Expense],
        ['code' => '5010', 'name' => 'Purchase Returns & Allowances', 'type' => AccountType::Expense],
        ['code' => '5020', 'name' => 'Cost of Service Parts', 'type' => AccountType::Expense],

        ['code' => '6000', 'name' => 'Rent Expense', 'type' => AccountType::Expense],
        ['code' => '6010', 'name' => 'Utility Expense', 'type' => AccountType::Expense],
        ['code' => '6020', 'name' => 'Salary Expense', 'type' => AccountType::Expense],
        ['code' => '6030', 'name' => 'Miscellaneous Expense', 'type' => AccountType::Expense],
        ['code' => '6040', 'name' => 'Inventory Shrinkage/Loss Expense', 'type' => AccountType::Expense],
        ['code' => '6045', 'name' => 'Inventory Count Adjustment', 'type' => AccountType::Expense],
        ['code' => '6050', 'name' => 'Bad Debt Expense', 'type' => AccountType::Expense],
        ['code' => '6060', 'name' => 'Finance Partner Settlement Loss', 'type' => AccountType::Expense],
        ['code' => '6070', 'name' => 'Finance Partner Fees Expense', 'type' => AccountType::Expense],
        ['code' => '6080', 'name' => 'Warranty Expense', 'type' => AccountType::Expense],
        ['code' => '6085', 'name' => 'Bank Charges & Fees', 'type' => AccountType::Expense],
        ['code' => '6086', 'name' => 'MFS & Payment Gateway Fees', 'type' => AccountType::Expense],
        ['code' => '6087', 'name' => 'Interest Expense', 'type' => AccountType::Expense],
        ['code' => '6088', 'name' => 'Cash Short / Miscellaneous Loss', 'type' => AccountType::Expense],

        // ── Payroll additions ─────────────────────────────────────────────────────
        ['code' => '1150', 'name' => 'Salary Advance Receivable','type' => AccountType::Asset],
        ['code' => '2031', 'name' => 'Tax Payable (Withheld)','type' => AccountType::Liability],
        ['code' => '2032', 'name' => 'Provident Fund Payable','type' => AccountType::Liability],
        ['code' => '2033', 'name' => 'Other Payroll Deductions Payable','type' => AccountType::Liability],
        ['code' => '6021', 'name' => 'Overtime Expense','type' => AccountType::Expense],
        ['code' => '6022', 'name' => 'Bonus Expense','type' => AccountType::Expense],
        ['code' => '6023', 'name' => 'Festival Bonus Expense','type' => AccountType::Expense],

        
    ];

    public function provisionForNewShop(Shop $shop, Branch $mainBranch): void
    {
        $cashHeader = null;

        foreach (self::DEFAULT_ACCOUNTS as $definition) {
            $account = Account::create([
                'shop_id' => $shop->id,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'type' => $definition['type'],
                'is_header' => $definition['is_header'] ?? false,
                'is_system' => true,
                'is_active' => true,
            ]);

            if ($definition['code'] === '1000') {
                $cashHeader = $account;
            }
        }

        $this->provisionCashAccountForBranch($shop, $mainBranch, $cashHeader);
    }

    /**
     * Call this again whenever a NEW branch is created later — every branch
     * needs its own Cash-in-Hand account and matching POS payment method,
     * since cash drawers are never shared across physical locations.
     */
    public function provisionCashAccountForBranch(Shop $shop, Branch $branch, ?Account $cashHeader = null): Account
    {
        $cashHeader ??= Account::where('shop_id', $shop->id)->where('code', '1000')->firstOrFail();

        $account = Account::create([
            'shop_id' => $shop->id,
            'branch_id' => $branch->id,
            'parent_id' => $cashHeader->id,
            'code' => $this->nextCashAccountCode($shop),
            'name' => "Cash in Hand - {$branch->name}",
            'type' => AccountType::Asset,
            'subtype' => 'cash',
            'is_system' => true,
            'is_active' => true,
        ]);

        PaymentAccount::create([
            'shop_id' => $shop->id,
            'branch_id' => $branch->id,
            'account_id' => $account->id,
            'name' => "Cash - {$branch->name}",
            'provider' => 'cash',
            'is_active' => true,
            'is_default' => true,
        ]);

        return $account;
    }

    /**
     * This is what the Owner will use (via a future Settings screen) to add
     * their own Bank/bKash/Nagad/Rocket/Upay accounts — directly answers
     * the "shop er to aro account thake" gap.
     */
    public function provisionCustomPaymentAccount(
        Shop $shop,
        string $name,
        string $provider, // 'bank', 'bkash', 'nagad', 'rocket', 'upay', 'card', 'other'
        ?string $accountNumber = null,
        ?string $bankName = null,
        ?int $branchId = null,
    ): PaymentAccount {
        $cashHeader = Account::where('shop_id', $shop->id)->where('code', '1000')->firstOrFail();

        $account = Account::create([
            'shop_id' => $shop->id,
            'branch_id' => $branchId,
            'parent_id' => $cashHeader->id,
            'code' => $this->nextBankAccountCode($shop),
            'name' => $name,
            'type' => AccountType::Asset,
            'subtype' => $provider === 'bank' ? 'bank' : 'mobile_banking',
            'is_system' => false,
            'is_active' => true,
        ]);

        return PaymentAccount::create([
            'shop_id' => $shop->id,
            'branch_id' => $branchId,
            'account_id' => $account->id,
            'name' => $name,
            'provider' => $provider,
            'account_number' => $accountNumber,
            'bank_name' => $bankName,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function nextCashAccountCode(Shop $shop): string
    {
        $count = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('subtype', 'cash')
            ->count();

        return (string) (1001 + $count);
    }

    private function nextBankAccountCode(Shop $shop): string
    {
        $count = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->whereIn('subtype', ['bank', 'mobile_banking'])
            ->count();

        return (string) (1051 + $count);
    }

    /**
     * Add treasury-specific GL accounts to an EXISTING shop without
     * disturbing any accounts already provisioned. Safe to call multiple times —
     * it skips codes that already exist.
     *
     * Called by: php artisan treasury:provision-gl-accounts
     */
    public function provisionTreasuryAccounts(Shop $shop): void
    {
        $newAccounts = [
            ['code' => '1300', 'name' => 'Petty Cash Control',              'type' => AccountType::Asset],
            ['code' => '2100', 'name' => 'Short-term Loans Payable',        'type' => AccountType::Liability],
            ['code' => '2200', 'name' => 'Long-term Loans Payable',         'type' => AccountType::Liability],
            ['code' => '3030', 'name' => 'Partner Capital',                 'type' => AccountType::Equity],
            ['code' => '4040', 'name' => 'Interest Income',                 'type' => AccountType::Revenue],
            ['code' => '4050', 'name' => 'Miscellaneous Income',            'type' => AccountType::Revenue],
            ['code' => '6085', 'name' => 'Bank Charges & Fees',             'type' => AccountType::Expense],
            ['code' => '6086', 'name' => 'MFS & Payment Gateway Fees',      'type' => AccountType::Expense],
            ['code' => '6087', 'name' => 'Interest Expense',                'type' => AccountType::Expense],
            ['code' => '6088', 'name' => 'Cash Short / Miscellaneous Loss', 'type' => AccountType::Expense],
        ];

        $existingCodes = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->pluck('code')
            ->toArray();

        foreach ($newAccounts as $definition) {
            if (! in_array($definition['code'], $existingCodes)) {
                Account::create([
                    'shop_id'   => $shop->id,
                    'code'      => $definition['code'],
                    'name'      => $definition['name'],
                    'type'      => $definition['type'],
                    'is_header' => false,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function provisionPayrollAccounts(Shop $shop): void
    {
        $newAccounts = [
            ['code' => '1150', 'name' => 'Salary Advance Receivable',       'type' => AccountType::Asset],
            ['code' => '2031', 'name' => 'Tax Payable (Withheld)',           'type' => AccountType::Liability],
            ['code' => '2032', 'name' => 'Provident Fund Payable',          'type' => AccountType::Liability],
            ['code' => '2033', 'name' => 'Other Payroll Deductions Payable','type' => AccountType::Liability],
            ['code' => '6021', 'name' => 'Overtime Expense',                'type' => AccountType::Expense],
            ['code' => '6022', 'name' => 'Bonus Expense',                   'type' => AccountType::Expense],
            ['code' => '6023', 'name' => 'Festival Bonus Expense',          'type' => AccountType::Expense],
        ];

        $existingCodes = Account::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->pluck('code')
            ->toArray();

        foreach ($newAccounts as $definition) {
            if (! in_array($definition['code'], $existingCodes)) {
                Account::create([
                    'shop_id'   => $shop->id,
                    'code'      => $definition['code'],
                    'name'      => $definition['name'],
                    'type'      => $definition['type'],
                    'is_header' => false,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }
        }
    }
}