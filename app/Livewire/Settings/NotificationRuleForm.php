<?php

namespace App\Livewire\Settings;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Enums\NotificationPriority;
use App\Enums\PermissionEnum;
use App\Models\NotificationRule;
use App\Models\User;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notification Rule')]
class NotificationRuleForm extends Component
{
    use HasAuthorization;

    public ?int $ruleId = null;
    public string $name = '';
    public string $event_type = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public array $conditions = [];
    public array $channel_override = [];
    public string $priority_override = '';

    public string $recipient_override_type = '';
    public string $recipient_override_permission = '';
    public array $recipient_override_user_ids = [];

    public function mount(?NotificationRule $rule = null): void
    {
        $this->requirePermission(PermissionEnum::NotificationsManageRules->value);

        if ($rule && $rule->exists) {
            $this->ruleId = $rule->id;
            $this->name = $rule->name;
            $this->event_type = $rule->event_type->value;
            $this->is_active = $rule->is_active;
            $this->sort_order = $rule->sort_order;
            $this->conditions = $rule->conditions ?? [];
            $this->channel_override = $rule->channel_override ?? [];
            $this->priority_override = $rule->priority_override?->value ?? '';
            $this->recipient_override_type = $rule->recipient_override_type ?? '';
            $this->recipient_override_permission = $rule->recipient_override_permission ?? '';
            $this->recipient_override_user_ids = $rule->recipient_override_user_ids ?? [];
        }
    }

    public function addCondition(): void
    {
        $this->conditions[] = ['field' => 'amount', 'operator' => '>', 'value' => ''];
    }

    public function removeCondition(int $index): void
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:150',
            'event_type' => 'required|string',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|in:>,>=,<,<=,==,!=',
            'conditions.*.value' => 'required|numeric',
        ]);

        NotificationRule::updateOrCreate(
            ['id' => $this->ruleId, 'shop_id' => Auth::user()->shop_id],
            [
                'shop_id' => Auth::user()->shop_id,
                'event_type' => $this->event_type,
                'name' => $this->name,
                'is_active' => $this->is_active,
                'sort_order' => $this->sort_order,
                'conditions' => array_map(fn ($c) => ['field' => $c['field'], 'operator' => $c['operator'], 'value' => (float) $c['value']], $this->conditions),
                'channel_override' => $this->channel_override ?: null,
                'priority_override' => $this->priority_override ?: null,
                'recipient_override_type' => $this->recipient_override_type ?: null,
                'recipient_override_permission' => $this->recipient_override_type === 'permission' ? $this->recipient_override_permission : null,
                'recipient_override_user_ids' => $this->recipient_override_type === 'users' ? $this->recipient_override_user_ids : null,
                'created_by' => Auth::id(),
            ]
        );

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Rule saved.']);
        $this->redirect(route('settings.notification-rules'), navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.notification-rule-form', [
            'eventTypes' => NotificationEventType::cases(),
            'channels' => NotificationChannel::cases(),
            'priorities' => NotificationPriority::cases(),
            'permissions' => PermissionEnum::cases(),
            'users' => User::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get(),
        ]);
    }
}