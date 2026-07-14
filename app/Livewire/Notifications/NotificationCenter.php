<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Notifications')]
class NotificationCenter extends Component
{
    use WithPagination;

    #[Url]
    public string $category = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $view = 'active'; // active | unread | pinned | archived

    public function updatingCategory(): void { $this->resetPage(); }
    public function updatingPriority(): void { $this->resetPage(); }
    public function updatingView(): void { $this->resetPage(); }

    public function markRead(int $id): void
    {
        NotificationRecipient::where('user_id', Auth::id())->findOrFail($id)->markRead();
    }

    public function markUnread(int $id): void
    {
        NotificationRecipient::where('user_id', Auth::id())->where('id', $id)->update(['read_at' => null]);
    }

    public function togglePin(int $id): void
    {
        $recipient = NotificationRecipient::where('user_id', Auth::id())->findOrFail($id);
        $recipient->update(['pinned_at' => $recipient->pinned_at ? null : now()]);
    }

    public function snooze(int $id, string $option): void
    {
        $until = match ($option) {
            '1h' => now()->addHour(),
            'tomorrow' => now()->addDay()->setTime(9, 0),
            'next_week' => now()->addWeek()->startOfWeek()->setTime(9, 0),
            default => null,
        };

        if ($until) {
            NotificationRecipient::where('user_id', Auth::id())->where('id', $id)
                ->update(['snoozed_until' => $until, 'read_at' => null]);
        }
    }

    public function archive(int $id): void
    {
        NotificationRecipient::where('user_id', Auth::id())->where('id', $id)
            ->update(['archived_at' => now(), 'dismissed_at' => now()]);
    }

    public function unarchive(int $id): void
    {
        NotificationRecipient::where('user_id', Auth::id())->where('id', $id)
            ->update(['archived_at' => null]);
    }

    public function dismiss(int $id): void
    {
        NotificationRecipient::where('user_id', Auth::id())->where('id', $id)
            ->update(['dismissed_at' => now()]);
    }

    public function markAllRead(): void
    {
        $this->baseQuery()->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function baseQuery()
    {
        $query = NotificationRecipient::with('notification')
            ->where('user_id', Auth::id());

        $query = match ($this->view) {
            'unread' => $query->whereNull('read_at')->whereNull('archived_at')->whereNull('dismissed_at'),
            'pinned' => $query->whereNotNull('pinned_at')->whereNull('archived_at'),
            'archived' => $query->whereNotNull('archived_at'),
            default => $query->whereNull('archived_at')->whereNull('dismissed_at')
                ->where(fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now())),
        };

        if ($this->category) {
            $query->whereHas('notification', fn ($q) => $q->where('category', $this->category));
        }

        if ($this->priority) {
            $query->whereHas('notification', fn ($q) => $q->where('priority', $this->priority));
        }

        return $query;
    }

    public function render()
    {
        $recipients = $this->baseQuery()->latest('created_at')->paginate(20);

        return view('livewire.notifications.notification-center', [
            'recipients' => $recipients,
            'categories' => NotificationCategory::cases(),
            'priorities' => NotificationPriority::cases(),
        ]);
    }
}