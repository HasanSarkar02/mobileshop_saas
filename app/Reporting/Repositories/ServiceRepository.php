<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceRepository extends BaseReportRepository
{
    public function stats(int $shopId, ?int $branchId = null): object
    {
        return DB::table('service_tickets')
            ->where('shop_id', $shopId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('
                COUNT(*) AS total,
                SUM(CASE WHEN status NOT IN ("delivered","cancelled") THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = "ready" THEN 1 ELSE 0 END) AS ready_for_pickup,
                SUM(CASE WHEN status != "cancelled" AND amount_due > 0 THEN 1 ELSE 0 END) AS with_due,
                COALESCE(SUM(CASE WHEN status != "cancelled" AND amount_due > 0 THEN amount_due ELSE 0 END), 0) AS total_due
            ')
            ->first();
    }

    public function revenueInPeriod(ReportFilter $filter): float
    {
        return (float) DB::table('service_payments')
            ->where('shop_id', $filter->shopId)
            ->whereBetween('payment_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->sum('amount');
    }

    public function technicianPerformance(ReportFilter $filter): Collection
    {
        return DB::table('service_tickets')
            ->join('users', 'users.id', '=', 'service_tickets.technician_id')
            ->where('service_tickets.shop_id', $filter->shopId)
            ->whereIn('service_tickets.status', ['delivered'])
            ->whereBetween('service_tickets.delivered_at', [
                $filter->dateRange->from,
                $filter->dateRange->to,
            ])
            ->when($filter->branchId, fn ($q) => $q->where('service_tickets.branch_id', $filter->branchId))
            ->selectRaw('
                users.id, users.name,
                COUNT(*) AS tickets_completed,
                COALESCE(SUM(total_charge), 0) AS total_revenue,
                COALESCE(AVG(TIMESTAMPDIFF(HOUR, received_at, delivered_at)), 0) AS avg_turnaround_hours
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByRaw('tickets_completed DESC')
            ->get();
    }

    public function openTickets(int $shopId, ?int $branchId = null): Collection
    {
        return DB::table('service_tickets')
            ->leftJoin('users', 'users.id', '=', 'service_tickets.technician_id')
            ->where('service_tickets.shop_id', $shopId)
            ->whereNotIn('service_tickets.status', ['delivered', 'cancelled'])
            ->when($branchId, fn ($q) => $q->where('service_tickets.branch_id', $branchId))
            ->select([
                'service_tickets.id', 'service_tickets.ticket_number',
                'service_tickets.customer_name', 'service_tickets.customer_phone',
                'service_tickets.device_model', 'service_tickets.status',
                'service_tickets.total_charge', 'service_tickets.amount_due',
                'service_tickets.received_at', 'users.name AS technician_name',
            ])
            ->orderBy('service_tickets.received_at')
            ->get();
    }
}