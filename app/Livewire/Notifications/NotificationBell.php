<?php

namespace App\Livewire\Notifications;

use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    /** @var \Illuminate\Support\Collection<int, NotificationRecipient> */
    public $items;

    public ?int $lastSeenId = null;

    public function mount(): void
    {
        $this->items = collect();
        $this->refresh();
    }

    public function refresh(): void
    {
        $query = fn () => NotificationRecipient::with(['notification', 'deliveries'])
            ->where('user_id', Auth::id())
            ->whereNull('archived_at')
            ->whereNull('dismissed_at')
            ->where(fn ($q) => $q->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));

        $this->unreadCount = $query()->whereNull('read_at')->count();

        $recent = $query()->latest('created_at')->limit(30)->get();

        $this->popNewArrivals($recent);
        $this->lastSeenId = $recent->max('id') ?? $this->lastSeenId;

        $this->items = $recent
            ->sort(function (NotificationRecipient $a, NotificationRecipient $b) {
                $pinned = ($b->pinned_at !== null) <=> ($a->pinned_at !== null);
                if ($pinned !== 0) {
                    return $pinned;
                }

                $priority = $b->notification->priority->weight() <=> $a->notification->priority->weight();
                if ($priority !== 0) {
                    return $priority;
                }

                return $b->created_at <=> $a->created_at;
            })
            ->take(8)
            ->values();
    }

    /**
     * Fires the SAME 'notify' browser event every existing Livewire component
     * already dispatches (see ProductForm/ProductList), so Popup-channel
     * notifications reuse the toast UI already wired in app.blade.php
     * verbatim instead of introducing a second toast system. Only fires for
     * recipients that actually received a Popup-channel delivery, and only
     * once per row (guarded by $lastSeenId, which only ever moves forward).
     */
    private function popNewArrivals($recent): void
    {
        if ($this->lastSeenId === null) {
            return; // first load — don't toast the user's entire backlog at once
        }

        $recent
            ->filter(fn (NotificationRecipient $r) => $r->id > $this->lastSeenId
                && $r->deliveries->contains(fn ($d) => $d->channel->value === 'popup'))
            ->each(function (NotificationRecipient $r) {
                $this->dispatch('notify', [
                    'type' => match ($r->notification->priority->value) {
                        'critical', 'urgent' => 'error',
                        'high' => 'warning',
                        default => 'info',
                    },
                    'message' => $r->notification->title,
                ]);
            });
    }

    public function markRead(int $recipientId): void
    {
        $recipient = NotificationRecipient::where('user_id', Auth::id())->findOrFail($recipientId);
        $recipient->markRead();
        $this->refresh();
    }

    public function markAllRead(): void
    {
        NotificationRecipient::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
        $this->refresh();
    }

    public function dismiss(int $recipientId): void
    {
        NotificationRecipient::where('user_id', Auth::id())->where('id', $recipientId)
            ->update(['dismissed_at' => now()]);
        $this->refresh();
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}