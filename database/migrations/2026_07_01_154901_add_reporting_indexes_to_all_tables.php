<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each index is added only if:
     * 1. The table exists
     * 2. The index doesn't already exist
     * This makes the migration fully idempotent and safe to re-run.
     */
    public function up(): void
    {
        $this->addIndex('sales', ['shop_id', 'status', 'confirmed_at'],     'sales_shop_status_date');
        $this->addIndex('sales', ['shop_id', 'cashier_id', 'status'],       'sales_shop_cashier_status');
        $this->addIndex('sales', ['shop_id', 'customer_id', 'status'],      'sales_shop_customer_status');
        $this->addIndex('sales', ['branch_id', 'status', 'confirmed_at'],   'sales_branch_status_date');
        $this->addIndex('sales', ['shop_id', 'return_processed', 'status'], 'sales_shop_return_status');

        $this->addIndex('sale_items', ['product_variant_id', 'sale_id'], 'sitems_variant_sale');
        $this->addIndex('sale_items', ['product_unit_id'],                'sitems_unit');
        $this->addIndex('sale_items', ['returned_quantity'],              'sitems_returned_qty');

        $this->addIndex('product_units', ['shop_id', 'product_variant_id', 'status'], 'punits_shop_variant_status');
        $this->addIndex('product_units', ['shop_id', 'branch_id', 'status'],          'punits_shop_branch_status');
        $this->addIndex('product_units', ['shop_id', 'status', 'is_archived'],        'punits_shop_status_archived');

        $this->addIndex('branch_stocks', ['shop_id', 'quantity'],                         'bstock_shop_quantity');
        $this->addIndex('branch_stocks', ['shop_id', 'product_variant_id', 'branch_id'], 'bstock_lookup');

        $this->addIndex('journal_entries', ['shop_id', 'entry_date'],                   'je_shop_date');
        $this->addIndex('journal_entries', ['shop_id', 'reference_type', 'entry_date'], 'je_shop_ref_date');
        $this->addIndex('journal_entries', ['branch_id', 'entry_date'],                 'je_branch_date');

        $this->addIndex('journal_entry_lines', ['account_id', 'journal_entry_id'], 'jel_account_entry');

        $this->addIndex('accounts', ['shop_id', 'code'],    'accounts_shop_code');
        $this->addIndex('accounts', ['shop_id', 'type'],    'accounts_shop_type');
        $this->addIndex('accounts', ['shop_id', 'subtype'], 'accounts_shop_subtype');

        $this->addIndex('expenses', ['shop_id', 'expense_date', 'status'],              'exp_shop_date_status');
        $this->addIndex('expenses', ['shop_id', 'expense_category_id', 'expense_date'], 'exp_cat_date');
        $this->addIndex('expenses', ['branch_id', 'expense_date', 'status'],            'exp_branch_date_status');

        $this->addIndex('service_tickets', ['shop_id', 'received_at', 'status'],   'svc_shop_date_status');
        $this->addIndex('service_tickets', ['shop_id', 'technician_id', 'status'], 'svc_technician_status');
        $this->addIndex('service_tickets', ['shop_id', 'amount_due'],              'svc_shop_due');

        $this->addIndex('service_payments', ['shop_id', 'payment_date'], 'svcpay_shop_date');

        $this->addIndex('salary_draws', ['shop_id', 'for_year', 'for_month', 'user_id'], 'draws_month_user');

        $this->addIndex('customers', ['shop_id', 'total_purchase_amount'], 'cust_shop_total_purchase');
        $this->addIndex('customers', ['shop_id', 'created_at'],            'cust_shop_created');

        $this->addIndex('customer_transactions', ['shop_id', 'created_at'], 'ctxn_shop_created');

        $this->addIndex('finance_partner_receivables', ['shop_id', 'finance_partner_id', 'status'], 'fpr_shop_partner_status');

        $this->addIndex('purchases', ['shop_id', 'purchase_date', 'payment_status'], 'pur_shop_date_status');
        $this->addIndex('purchases', ['shop_id', 'supplier_id', 'purchase_date'],    'pur_shop_supplier_date');

        $this->addIndex('credit_notes', ['shop_id', 'created_at', 'status'], 'cn_shop_date_status');

        $this->addIndex('used_phone_acquisitions', ['shop_id', 'created_at'], 'upa_shop_created');
        $this->addIndex('used_phone_acquisitions', ['shop_id', 'branch_id'],  'upa_shop_branch');

        $this->addIndex('payroll_runs', ['shop_id', 'year', 'month'],      'pr_shop_year_month');
        $this->addIndex('payroll_runs', ['shop_id', 'status'],              'pr_shop_status');

        $this->addIndex('salary_advances', ['shop_id', 'user_id', 'status'], 'sadv_shop_user_status');
    }

    public function down(): void
    {
        // Indexes are advisory — dropping is optional.
        // Add explicit drops here if you need clean rollbacks.
    }

    /**
     * Safely add an index:
     *  - skips if table doesn't exist (table may not be migrated yet in some environments)
     *  - skips if index already exists (idempotent)
     *  - skips if any column in the index doesn't exist in the table
     */
    private function addIndex(string $table, array $columns, string $indexName): void
{
    if (! Schema::hasTable($table)) {
        return;
    }

    if ($this->indexExists($table, $indexName)) {
        return;
    }

    foreach ($columns as $column) {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }
    }

    Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
        $blueprint->index($columns, $indexName);
    });
}

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
                [$indexName]
            );
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};