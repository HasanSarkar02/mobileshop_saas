<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Notifications</h1>
        <div class="flex gap-2">
            <a href="{{ route('notifications.preferences') }}" wire:navigate class="btn-secondary btn-sm">Preferences</a>
            <button wire:click="markAllRead" class="btn-secondary btn-sm">Mark all read</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        <select wire:model.live="view" class="input">
            <option value="active">Active</option>
            <option value="unread">Unread</option>
            <option value="pinned">Pinned</option>
            <option value="archived">Archived</option>
        </select>

        <select wire:model.live="category" class="input">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="priority" class="input">
            <option value="">All priorities</option>
            @foreach ($priorities as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="card divide-y">
        @forelse($recipients as $recipient)
            @php($notification = $recipient->notification)
            <div class="flex items-start gap-3 px-4 py-3 {{ $recipient->read_at ? '' : 'bg-blue-50/40' }}">
                <span class="badge {{ $notification->priority->badgeClass() }} shrink-0 mt-1">
                    {{ $notification->priority->label() }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-medium">{{ $notification->title }}</p>
                        @if ($notification->action_required && !$recipient->action_taken_at)
                            <span
                                class="badge badge-yellow">{{ $notification->action_label ?? 'Action required' }}</span>
                        @endif
                        @if ($recipient->pinned_at)
                            <span class="badge badge-indigo">Pinned</span>
                        @endif
                        @if ($notification->occurrence_count > 1)
                            <span class="badge badge-gray">×{{ $notification->occurrence_count }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">{{ $notification->body }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $notification->category->label() }} &middot; {{ $recipient->created_at->diffForHumans() }}
                    </p>
                </div>

                <div class="flex items-center gap-1 shrink-0 flex-wrap justify-end">
                    @if ($notification->payload['deep_link'] ?? null)
                        <a href="{{ $notification->payload['deep_link'] }}" wire:navigate
                            wire:click="markRead({{ $recipient->id }})" class="btn-secondary btn-sm">View</a>
                    @endif

                    @if ($recipient->read_at)
                        <button wire:click="markUnread({{ $recipient->id }})"
                            class="btn-secondary btn-sm">Unread</button>
                    @else
                        <button wire:click="markRead({{ $recipient->id }})" class="btn-secondary btn-sm">Read</button>
                    @endif

                    <button wire:click="togglePin({{ $recipient->id }})" class="btn-secondary btn-sm">
                        {{ $recipient->pinned_at ? 'Unpin' : 'Pin' }}
                    </button>

                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                            class="btn-secondary btn-sm">Snooze</button>
                        <div x-show="open" x-cloak class="absolute right-0 mt-1 w-40 card shadow z-10">
                            <button wire:click="snooze({{ $recipient->id }}, '1h')"
                                class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">1 hour</button>
                            <button wire:click="snooze({{ $recipient->id }}, 'tomorrow')"
                                class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">Tomorrow</button>
                            <button wire:click="snooze({{ $recipient->id }}, 'next_week')"
                                class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">Next week</button>
                        </div>
                    </div>

                    @if ($recipient->archived_at)
                        <button wire:click="unarchive({{ $recipient->id }})"
                            class="btn-secondary btn-sm">Unarchive</button>
                    @else
                        <button wire:click="archive({{ $recipient->id }})"
                            class="btn-secondary btn-sm">Archive</button>
                    @endif

                    <button wire:click="dismiss({{ $recipient->id }})" class="btn-danger btn-sm">Dismiss</button>
                </div>
            </div>
        @empty
            <p class="px-4 py-10 text-center text-gray-500">Nothing here.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $recipients->links() }}</div>
</div>
