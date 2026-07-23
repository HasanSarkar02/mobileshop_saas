<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notification Preferences')]
class NotificationPreferences extends Component
{
    /** @var array<string, array<string, bool>> [category => [channel => bool]] */
    public array $prefs = [];

    public function mount(): void
    {
        $existing = NotificationPreference::where('user_id', Auth::id())
            ->get()
            ->groupBy(fn ($p) => $p->category->value);

        foreach (NotificationCategory::cases() as $category) {
            foreach (NotificationChannel::cases() as $channel) {
                if (! $channel->isImplemented() || $channel === NotificationChannel::InApp) {
                    continue;
                }

                $stored = $existing->get($category->value)?->firstWhere('channel', $channel);
                // Default to on for channels genuinely available today; absence
                // of a row already means "use event default" at dispatch time,
                // but the toggle needs a starting visual state.
                $this->prefs[$category->value][$channel->value] = $stored?->is_enabled ?? true;
            }
        }
    }

    public function save(): void
    {
        foreach ($this->prefs as $category => $channels) {
            foreach ($channels as $channel => $enabled) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => Auth::id(), 'category' => $category, 'channel' => $channel],
                    ['shop_id' => Auth::user()->shop_id, 'is_enabled' => $enabled]
                );
            }
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Notification preferences saved.']);
    }

    public function render()
    {
        return view('livewire.notifications.notification-preferences', [
            'categories' => NotificationCategory::cases(),
            'channels' => array_filter(NotificationChannel::cases() , fn ($c) => $c->isImplemented() && $c !== NotificationChannel::InApp),
        ]);
    }
}