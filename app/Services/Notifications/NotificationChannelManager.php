<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Services\Notifications\Channels\EmailChannelHandler;
use App\Services\Notifications\Channels\InAppChannelHandler;
use App\Services\Notifications\Channels\PopupChannelHandler;
use App\Services\Notifications\Channels\PushChannelHandler;
use App\Services\Notifications\Channels\SmsChannelHandler;
use App\Services\Notifications\Channels\WhatsAppChannelHandler;
use App\Services\Notifications\Contracts\NotificationChannelHandler;

class NotificationChannelManager
{
    public function handlerFor(NotificationChannel $channel): NotificationChannelHandler
    {
        return match ($channel) {
            NotificationChannel::InApp => app(InAppChannelHandler::class),
            NotificationChannel::Popup => app(PopupChannelHandler::class),
            NotificationChannel::Sms => app(SmsChannelHandler::class),
            NotificationChannel::Email => app(EmailChannelHandler::class),
            NotificationChannel::Push => app(PushChannelHandler::class),
            NotificationChannel::WhatsApp => app(WhatsAppChannelHandler::class),
        };
    }
}