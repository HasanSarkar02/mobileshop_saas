<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollRunStatus;
use App\Enums\PayrollSlipStatus;
use App\Models\PayrollAuditLog;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelPayrollRunAction
{
    public function execute(PayrollRun $run, string $reason, User $actor): PayrollRun
    {
        if (! $run->status->canBeCancelled()) {
            throw new \RuntimeException(
                "Cannot cancel a payroll run with status: {$run->status->label()}. " .
                "Only Draft and Under Review runs can be cancelled."
            );
        }

        if (strlen(trim($reason)) < 5) {
            throw new \RuntimeException("Please provide a meaningful cancellation reason.");
        }

        return DB::transaction(function () use ($run, $reason, $actor) {
            $run->update([
                'status'               => PayrollRunStatus::Cancelled->value,
                'cancelled_by'         => $actor->id,
                'cancelled_at'         => now(),
                'cancellation_reason'  => $reason,
            ]);

            // Cancel all slips
            $run->slips()->update(['status' => PayrollSlipStatus::Cancelled->value]);

            PayrollAuditLog::record(
                shopId:        $run->shop_id,
                referenceType: 'payroll_runs',
                referenceId:   $run->id,
                action:        PayrollAuditAction::Cancelled,
                oldStatus:     $run->status->value,
                newStatus:     PayrollRunStatus::Cancelled->value,
                reason:        $reason,
            );

            return $run->fresh();
        });
    }
}