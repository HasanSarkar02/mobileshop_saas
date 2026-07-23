<?php

namespace App\Livewire\Settings;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\NotificationTemplate;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notification Templates')]
class NotificationTemplateList extends Component
{
    use HasAuthorization;

    public function mount(): void
    {
        $this->requirePermission(PermissionEnum::NotificationsManageTemplates->value);
    }

    public function resetToSystemDefault(int $shopTemplateId): void
    {
        NotificationTemplate::where('id', $shopTemplateId)->where('shop_id', Auth::user()->shop_id)->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Reverted to the system default template.']);
    }

    public function render()
    {
        $shopId = Auth::user()->shop_id;

        $systemTemplates = NotificationTemplate::withoutGlobalScopes()->whereNull('shop_id')->get()
            ->keyBy(fn ($t) => $t->event_type->value . ':' . $t->channel->value);

        $shopTemplates = NotificationTemplate::withoutGlobalScopes()->where('shop_id', $shopId)->get()
            ->keyBy(fn ($t) => $t->event_type->value . ':' . $t->channel->value);

        $rows = collect();

        foreach (NotificationEventType::cases() as $eventType) {
            foreach ([NotificationChannel::Email, NotificationChannel::Sms] as $channel) {
                $key = $eventType->value . ':' . $channel->value;
                $rows->push([
                    'event_type' => $eventType,
                    'channel' => $channel,
                    'system' => $systemTemplates->get($key),
                    'override' => $shopTemplates->get($key),
                ]);
            }
        }

        return view('livewire.settings.notification-template-list', ['rows' => $rows]);
    }
}