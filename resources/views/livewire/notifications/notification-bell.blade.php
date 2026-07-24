<div class="relative" x-data="{ open: false }" wire:poll.30s="refresh">
    <button @click="open = !open" @click.outside="open = false" class="relative p-2 rounded-full hover:bg-gray-100"
        aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unreadCount > 0)
            <span
                class="absolute -top-1 -right-1 badge badge-red rounded-full text-xs px-1.5 py-0.5 min-w-[18px] text-center leading-tight">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition
        class="fixed left-4 right-4 top-16 z-50
               sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96
               card shadow-lg max-h-[70vh] sm:max-h-[28rem] overflow-y-auto">
        <div class="flex items-center justify-between px-4 py-2 border-b">
            <span class="font-semibold">Notifications</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllRead" class="text-sm text-blue-600 hover:underline">Mark all read</button>
            @endif
        </div>

        @forelse($items as $recipient)
            <a href="{{ $recipient->notification->payload['deep_link'] ?? route('notifications.index') }}" wire:navigate
                wire:click="markRead({{ $recipient->id }})"
                class="flex gap-3 px-4 py-3 border-b hover:bg-gray-50 {{ $recipient->read_at ? '' : 'bg-blue-50/50' }}">
                <span
                    class="badge {{ $recipient->notification->priority->badgeClass() }} shrink-0 self-start text-[10px] px-1.5 py-0.5">
                    {{ $recipient->notification->priority->label() }}
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ $recipient->notification->title }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ $recipient->notification->body }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $recipient->created_at->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <p class="px-4 py-6 text-sm text-gray-500 text-center">You're all caught up.</p>
        @endforelse

        <a href="{{ route('notifications.index') }}" wire:navigate
            class="block text-center text-sm text-blue-600 py-2 hover:underline">
            View all notifications
        </a>
    </div>
</div>
