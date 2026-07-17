<?php

namespace App\Actions;

use App\Mail\NotificationMail;
use App\Models\Shop;
use App\Services\Notifications\DynamicMailerConfigurator;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTestEmailAction
{
    public function __construct(private readonly DynamicMailerConfigurator $mailerConfig) {}

    public function execute(Shop $shop, string $toEmail): array
    {
        try {
            $mailerName = $this->mailerConfig->configure($shop);

            Mail::mailer($mailerName)
                ->to($toEmail)
                ->send((new NotificationMail(
                    mailSubject: 'Test email from ' . $shop->name,
                    bodyText: "This is a test email confirming your SMTP settings for {$shop->name} are working correctly.",
                    shopName: $shop->name,
                ))->from(
                    $shop->smtp_from_address ?: $shop->email,
                    $shop->smtp_from_name ?: $shop->name,
                ));

            return ['success' => true, 'message' => "Test email sent to {$toEmail}."];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}