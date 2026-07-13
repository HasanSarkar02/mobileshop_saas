<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollAuditAction;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollAuditLog;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitPayrollForReviewAction
{
    public function execute(PayrollRun $run, User $actor): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::Draft) {
            throw new \RuntimeException(
                "Only draft payroll runs can be submitted for review."
            );
        }

        if ($run->total_employees === 0) {
            throw new \RuntimeException("Cannot submit an empty payroll run.");
        }

        return DB::transaction(function () use ($run, $actor) {
            $run->update([
                'status'      => PayrollRunStatus::UnderReview->value,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            PayrollAuditLog::record(
                shopId:        $run->shop_id,
                referenceType: 'payroll_runs',
                referenceId:   $run->id,
                action:        PayrollAuditAction::SubmittedReview,
                oldStatus:     PayrollRunStatus::Draft->value,
                newStatus:     PayrollRunStatus::UnderReview->value,
            );

            return $run->fresh();
        });
    }
}