<?php
namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    public static function apply(Shop $shop)
    {
        if (!$shop->smtp_enabled) return;

        Config::set('mail.mailers.smtp.host', $shop->smtp_host);
        Config::set('mail.mailers.smtp.port', $shop->smtp_port);
        Config::set('mail.mailers.smtp.username', $shop->smtp_username);
        Config::set('mail.mailers.smtp.password', $shop->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $shop->smtp_encryption ?? 'tls');
        
        Config::set('mail.from.address', $shop->smtp_from_address);
        Config::set('mail.from.name', $shop->smtp_from_name);
        
        Config::set('mail.default', 'smtp');
    }
}