<?php

namespace App\Enums;

enum PayrollAuditAction: string
{
    case Generated        = 'generated';
    case SubmittedReview  = 'submitted_for_review';
    case Approved         = 'approved';
    case Rejected         = 'rejected';
    case Cancelled        = 'cancelled';
    case PaymentMade      = 'payment_made';
    case PaymentReversed  = 'payment_reversed';
    case Reversed         = 'reversed';
    case LoanDisbursed    = 'loan_disbursed';
    case LoanRecovered    = 'loan_recovered';
    case LoanWaived       = 'loan_waived';
}