<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\SmsLog;
use App\Services\Sms\BulkSmsBdProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SslCommerzSmsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsService
{
    // ── SMS Templates ──────────────────────────────────────────────────────────

    public function saleReceiptMessage(Shop $shop, string $customerName, float $amount, string $invoiceNo): string
    {
        return "{$shop->name}: Thank you {$customerName}! " .
               "Invoice {$invoiceNo} - Amount: Tk {$amount}. " .
               "For queries call {$shop->phone}.";
    }

    public function dueReminderMessage(Shop $shop, string $customerName, float $dueAmount): string
    {
        return "{$shop->name}: Dear {$customerName}, " .
               "your outstanding balance is Tk " . number_format($dueAmount, 2) . ". " .
               "Please clear at your earliest. Call: {$shop->phone}.";
    }

    public function serviceReadyMessage(Shop $shop, string $customerName, string $deviceModel, string $ticketNo): string
    {
        return "{$shop->name}: Dear {$customerName}, " .
               "your {$deviceModel} (Ticket: {$ticketNo}) is ready for pickup. " .
               "Contact: {$shop->phone}.";
    }

    public function customMessage(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }

    // ── Send Methods ───────────────────────────────────────────────────────────

    public function sendSaleReceipt(Shop $shop, \App\Models\Customer $customer, \App\Models\Sale $sale): bool
    {
        if (! $this->canSend($shop, 'sms_on_sale')) return false;
        if (! $customer->phone || $customer->customer_type->value === 'walk_in') return false;

        $message = $this->saleReceiptMessage(
            $shop,
            $customer->name,
            (float) $sale->grand_total,
            $sale->sale_number,
        );

        return $this->send($shop, $customer->phone, $message, 'sale_receipt', $sale);
    }

    public function sendDueReminder(Shop $shop, \App\Models\Customer $customer): bool
    {
        if (! $this->canSend($shop, 'sms_on_due_reminder')) return false;
        if (! $customer->phone || $customer->current_balance <= 0) return false;

        $message = $this->dueReminderMessage($shop, $customer->name, (float) $customer->current_balance);

        return $this->send($shop, $customer->phone, $message, 'due_reminder', $customer);
    }

    public function sendServiceReady(Shop $shop, \App\Models\ServiceTicket $ticket): bool
    {
        if (! $this->canSend($shop, 'sms_on_service_ready')) return false;
        if (! $ticket->customer_phone) return false;

        $message = $this->serviceReadyMessage(
            $shop,
            $ticket->customer_name,
            $ticket->device_model ?? 'device',
            $ticket->ticket_number,
        );

        return $this->send($shop, $ticket->customer_phone, $message, 'service_ready', $ticket);
    }

    // ── Core Send ──────────────────────────────────────────────────────────────

    public function send(
        Shop    $shop,
        string  $to,
        string  $message,
        string  $template,
        ?Model  $reference = null,
        ?int    $createdBy = null,
    ): bool {
        if (! $shop->sms_enabled || ! $shop->sms_api_key) {
            return false;
        }

        $provider  = $this->resolveProvider($shop);
        $messageId = null;
        $status    = 'failed';

        try {
            $messageId = $provider->send($to, $message, $shop->sms_sender_id ?? $shop->name);
            $status    = $messageId ? 'sent' : 'failed';
        } catch (\Throwable $e) {
            Log::error('SMS send error', ['shop_id' => $shop->id, 'error' => $e->getMessage()]);
        }

        SmsLog::create([
            'shop_id'            => $shop->id,
            'to_number'          => $to,
            'template'           => $template,
            'message'            => $message,
            'status'             => $status,
            'message_id'         => $messageId,
            'provider_response'  => $messageId ?? 'failed',
            'reference_type'     => $reference?->getMorphClass(),
            'reference_id'       => $reference?->getKey(),
            'created_by'         => $createdBy ?? Auth::id(),
        ]);

        return $status === 'sent';
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function canSend(Shop $shop, string $feature): bool
    {
        return $shop->sms_enabled && $shop->sms_api_key && $shop->$feature;
    }

    private function resolveProvider(Shop $shop): SmsProviderInterface
    {
        return match ($shop->sms_provider) {
            'ssl_commerz' => new SslCommerzSmsProvider($shop->sms_api_key),
            default       => new BulkSmsBdProvider($shop->sms_api_key),
        };
    }
}