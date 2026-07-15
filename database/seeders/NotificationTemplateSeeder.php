<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    private const TEMPLATES = [
        ['event' => NotificationEventType::ExpensePendingApproval, 'channel' => NotificationChannel::Email,
            'subject' => 'Expense needs your approval — {{amount}}',
            'body' => "An expense of {{amount}} was submitted and is waiting for approval.\n\nBranch: {{branch}}\nDate: {{date}}"],

        ['event' => NotificationEventType::TreasuryPendingApproval, 'channel' => NotificationChannel::Email,
            'subject' => 'Treasury transaction needs your approval',
            'body' => "A treasury transaction of {{amount}} is waiting for approval.\n\nBranch: {{branch}}\nDate: {{date}}"],

        ['event' => NotificationEventType::CustomerDueReminder, 'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Dear {{customer_name}}, your outstanding balance is {{amount}}. Please clear at your earliest convenience.'],

        ['event' => NotificationEventType::ServiceTicketReady, 'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Dear {{customer_name}}, your device is ready for pickup. Invoice: {{invoice_no}}.'],

        ['event' => NotificationEventType::ImpersonationStarted, 'channel' => NotificationChannel::Email,
            'subject' => 'Security notice: your account was accessed by support',
            'body' => 'A Super Admin started a support session on your shop account on {{date}}. If you did not expect this, please contact support immediately.'],

        ['event' => NotificationEventType::SalaryOverdrawn, 'channel' => NotificationChannel::Email,
            'subject' => 'Employee salary overdrawn — {{employee_name}}',
            'body' => '{{employee_name}} has drawn more than their gross salary for this period ({{amount}} over).'],

        ['event' => NotificationEventType::SupplierPaymentDue, 'channel' => NotificationChannel::Email,
            'subject' => 'Supplier payments due — {{supplier_name}}',
            'body' => 'One or more payments to {{supplier_name}} are now due. Amount: {{amount}}.'],

        ['event' => NotificationEventType::PayrollReminderDue, 'channel' => NotificationChannel::Email,
            'subject' => 'Payroll reminder',
            'body' => 'Payroll for {{branch}} has not been generated yet for this period. Date: {{date}}.'],

        ['event' => NotificationEventType::LoanRepaymentDue, 'channel' => NotificationChannel::Email,
            'subject' => 'Loan repayment reminder',
            'body' => 'Outstanding loan balance: {{amount}}. No repayment recorded recently.'],
    ];

    public function run(): void
    {
        foreach (self::TEMPLATES as $t) {
            NotificationTemplate::withoutGlobalScopes()->firstOrCreate(
                ['shop_id' => null, 'event_type' => $t['event']->value, 'channel' => $t['channel']->value],
                ['subject' => $t['subject'], 'body' => $t['body'], 'is_active' => true]
            );
        }
    }
}