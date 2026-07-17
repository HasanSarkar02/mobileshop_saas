<?php

namespace App\Livewire\Settings;

use App\Enums\NotificationEventType;
use App\Enums\PermissionEnum;
use App\Models\NotificationTemplate;
use App\Services\Notifications\TemplateRenderer;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notification Template')]
class NotificationTemplateForm extends Component
{
    use HasAuthorization;

    public string $event_type = '';
    public string $channel = '';
    public string $subject = '';
    public string $body = '';
    public bool $is_active = true;

    public function mount(string $eventType, string $channel): void
    {
        $this->requirePermission(PermissionEnum::NotificationsManageTemplates->value);

        $this->event_type = $eventType;
        $this->channel = $channel;

        $existing = NotificationTemplate::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where('event_type', $eventType)
            ->where('channel', $channel)
            ->first();

        if ($existing) {
            $this->subject = $existing->subject ?? '';
            $this->body = $existing->body;
            $this->is_active = $existing->is_active;
        } else {
            $system = NotificationTemplate::withoutGlobalScopes()
                ->whereNull('shop_id')->where('event_type', $eventType)->where('channel', $channel)->first();
            $this->subject = $system?->subject ?? '';
            $this->body = $system?->body ?? '';
        }
    }

    #[Computed]
    public function previewSubject(): string
    {
        return strtr($this->subject, $this->wrappedSample());
    }

    #[Computed]
    public function previewBody(): string
    {
        return strtr($this->body, $this->wrappedSample());
    }

    private function wrappedSample(): array
    {
        $wrapped = [];
        foreach (TemplateRenderer::samplePlaceholders() as $key => $value) {
            $wrapped['{{' . $key . '}}'] = $value;
        }
        return $wrapped;
    }

    public function save(): void
    {
        $this->validate(['body' => 'required|string', 'subject' => 'nullable|string|max:255']);

        NotificationTemplate::updateOrCreate(
            ['shop_id' => Auth::user()->shop_id, 'event_type' => $this->event_type, 'channel' => $this->channel],
            ['subject' => $this->subject ?: null, 'body' => $this->body, 'is_active' => $this->is_active]
        );

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Template saved.']);
        $this->redirect(route('settings.notification-templates'), navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.notification-template-form', [
            'eventTypeLabel' => NotificationEventType::from($this->event_type)->label(),
            'placeholders' => TemplateRenderer::availablePlaceholders(),
        ]);
    }
}