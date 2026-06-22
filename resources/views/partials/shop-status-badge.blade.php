@php
    $classes = match ($status?->value ?? '') {
        'active' => 'badge-green',
        'trial' => 'badge-blue',
        'suspended' => 'badge-red',
        'expired' => 'badge-gray',
        default => 'badge-gray',
    };
@endphp
<span class="badge {{ $classes }}">{{ ucfirst($status?->value ?? '—') }}</span>
