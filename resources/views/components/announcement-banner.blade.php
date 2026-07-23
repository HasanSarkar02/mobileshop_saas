@props(['audience' => 'both'])

@php
    $announcements = \App\Models\PlatformAnnouncement::query()
        ->active()
        ->forAudience($audience)
        ->orderByDesc('id')
        ->get();
@endphp

@if ($announcements->isNotEmpty())
    <div x-data="{
        dismissed: JSON.parse(localStorage.getItem('dismissed_announcements') || '[]'),
        dismiss(id) {
            this.dismissed.push(id);
            localStorage.setItem('dismissed_announcements', JSON.stringify(this.dismissed));
        }
    }" class="space-y-2 mb-4">
        @foreach ($announcements as $a)
            <div x-show="!dismissed.includes({{ $a->id }})"
                class="rounded-xl px-4 py-3 text-sm flex items-start gap-3
                    {{ match ($a->type) {
                        'critical' => 'bg-red-50 border border-red-200 text-red-800',
                        'warning' => 'bg-amber-50 border border-amber-200 text-amber-800',
                        default => 'bg-indigo-50 border border-indigo-200 text-indigo-800',
                    } }}">
                <div class="flex-1">
                    <div class="font-semibold">{{ $a->title }}</div>
                    <div class="mt-0.5">{{ $a->body }}</div>
                </div>
                @if ($a->dismissible)
                    <button @click="dismiss({{ $a->id }})"
                        class="shrink-0 opacity-60 hover:opacity-100 text-lg leading-none">✕</button>
                @endif
            </div>
        @endforeach
    </div>
@endif
