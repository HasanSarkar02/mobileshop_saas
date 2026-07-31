<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerRepository extends BaseReportRepository
{
    /** Customers by balance (for due aging) */
    public function dueAging(int $shopId, ?int $branchId = null): Collection
    {
        return DB::table('customers')
            ->where('shop_id', $shopId)
            ->where('customer_type', '!=', 'walk_in')
            ->where('current_balance', '>', 0)
            ->orderByDesc('current_balance')
            ->get([
                'id', 'name', 'phone', 'customer_type',
                'current_balance', 'credit_limit', 'created_at','total_purchase_amount',
            ]);
    }

    public function followUpsDueToday(int $shopId): Collection
    {
        return DB::table('customer_due_follow_ups')
            ->join('customers', 'customers.id', '=', 'customer_due_follow_ups.customer_id')
            ->where('customer_due_follow_ups.shop_id', $shopId)
            ->whereDate('customer_due_follow_ups.next_followup_date', now()->toDateString())
            ->whereNull('customer_due_follow_ups.completed_at')
            ->whereNotIn('customer_due_follow_ups.status', ['paid', 'cancelled'])
            ->orderBy('customers.name')
            ->get([
                'customer_due_follow_ups.id', 'customers.id as customer_id',
                'customers.name as customer_name', 'customers.current_balance',
                'customer_due_follow_ups.next_followup_date', 'customer_due_follow_ups.status',
            ]);
    }

    public function overdueFollowUps(int $shopId): Collection
    {
        return DB::table('customer_due_follow_ups')
            ->join('customers', 'customers.id', '=', 'customer_due_follow_ups.customer_id')
            ->where('customer_due_follow_ups.shop_id', $shopId)
            ->whereDate('customer_due_follow_ups.next_followup_date', '<', now()->toDateString())
            ->whereNull('customer_due_follow_ups.completed_at')
            ->whereNotIn('customer_due_follow_ups.status', ['paid', 'cancelled'])
            ->orderBy('customer_due_follow_ups.next_followup_date')
            ->get([
                'customer_due_follow_ups.id', 'customers.id as customer_id',
                'customers.name as customer_name', 'customers.current_balance',
                'customer_due_follow_ups.next_followup_date', 'customer_due_follow_ups.status',
            ]);
    }

    public function brokenPromises(int $shopId): Collection
    {
        return DB::table('customer_due_follow_ups')
            ->join('customers', 'customers.id', '=', 'customer_due_follow_ups.customer_id')
            ->where('customer_due_follow_ups.shop_id', $shopId)
            ->whereDate('customer_due_follow_ups.promised_payment_date', '<', now()->toDateString())
            ->whereNull('customer_due_follow_ups.completed_at')
            ->whereNotIn('customer_due_follow_ups.status', ['paid', 'cancelled'])
            ->where('customers.current_balance', '>', 0)
            ->orderBy('customer_due_follow_ups.promised_payment_date')
            ->get([
                'customer_due_follow_ups.id', 'customers.id as customer_id',
                'customers.name as customer_name', 'customers.current_balance',
                'customer_due_follow_ups.promised_payment_date', 'customer_due_follow_ups.promised_amount',
            ]);
    }

    /** Customer summary stats */
    public function stats(int $shopId): object
    {
        return DB::table('customers')
            ->where('shop_id', $shopId)
            ->where('customer_type', '!=', 'walk_in')
            ->selectRaw('
                COUNT(*) AS total,
                SUM(CASE WHEN current_balance > 0 THEN 1 ELSE 0 END) AS with_due,
                COALESCE(SUM(current_balance), 0) AS total_outstanding,
                COALESCE(SUM(total_purchase_amount), 0) AS total_purchases
            ')
            ->first();
    }

    /** Finance partner receivables aged */
    public function fpReceivablesAging(int $shopId, ?int $partnerId = null): Collection
    {
        return DB::table('finance_partner_receivables')
            ->join('finance_partners', 'finance_partners.id', '=', 'finance_partner_receivables.finance_partner_id')
            ->join('sales', 'sales.id', '=', 'finance_partner_receivables.sale_id')
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('finance_partner_receivables.shop_id', $shopId)
            ->whereIn('finance_partner_receivables.status', ['pending', 'partial'])
            ->when($partnerId, fn ($q) => $q->where('finance_partner_receivables.finance_partner_id', $partnerId))
            ->selectRaw('
                finance_partners.name AS partner_name,
                sales.sale_number,
                customers.name AS customer_name,
                finance_partner_receivables.total_amount,
                finance_partner_receivables.settled_amount,
                (finance_partner_receivables.total_amount - finance_partner_receivables.settled_amount) AS pending_amount,
                finance_partner_receivables.status,
                sales.confirmed_at AS sale_date,
                DATEDIFF(NOW(), sales.confirmed_at) AS days_outstanding
            ')
            ->orderByRaw('days_outstanding DESC')
            ->get();
    }
}