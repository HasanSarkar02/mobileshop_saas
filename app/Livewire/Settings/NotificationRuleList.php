<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionEnum;
use App\Models\NotificationRule;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notification Rules')]
class NotificationRuleList extends Component
{
    use HasAuthorization;

    public function mount(): void
    {
        $this->requirePermission(PermissionEnum::NotificationsManageRules->value);
    }

    public function toggleActive(int $id): void
    {
        $rule = NotificationRule::where('id', $id)->where('shop_id', Auth::user()->shop_id)->firstOrFail();
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function delete(int $id): void
    {
        NotificationRule::where('id', $id)->where('shop_id', Auth::user()->shop_id)->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Rule deleted.']);
    }

    public function render()
    {
        return view('livewire.settings.notification-rule-list', [
            'rules' => NotificationRule::where('shop_id', Auth::user()->shop_id)->orderBy('event_type')->orderBy('sort_order')->get(),
        ]);
    }
}