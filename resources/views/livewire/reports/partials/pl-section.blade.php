@php
    $rows = $rows ?? [];
    $total = $total ?? 0;
    $totalLabel = $totalLabel ?? 'Total';
    $title = $title ?? '';
    $titleColor = $titleColor ?? 'text-gray-700';
    $totalBold = $totalBold ?? false;
    $prev = $prev ?? null;
@endphp

<div class="px-6 py-3 bg-gray-50">
    <span class="text-xs font-bold {{ $titleColor }} uppercase tracking-wider">{{ $title }}</span>
</div>

@foreach ($rows as $row)
    @if (($row['amount'] ?? 0) != 0)
        <div class="px-6 py-2.5 flex items-center justify-between hover:bg-gray-50">
            <span class="text-sm text-gray-700 {{ $row['indent'] ?? 0 ? 'pl-' . $row['indent'] * 4 : '' }}">
                {{ $row['label'] }}
            </span>
            <span class="text-sm {{ $row['contra'] ?? false ? 'text-red-500' : 'text-gray-900' }} font-medium">
                {{ ($row['contra'] ?? false) && $row['amount'] < 0 ? '(৳' . number_format(abs($row['amount']), 2) . ')' : '৳' . number_format($row['amount'], 2) }}
            </span>
        </div>
    @endif
@endforeach

<div class="px-6 py-3 flex justify-between border-t border-gray-200">
    <span class="{{ $totalBold ? 'font-bold text-gray-900' : 'font-semibold text-gray-700' }}">
        {{ $totalLabel }}
    </span>
    <div class="text-right">
        <span class="{{ $totalBold ? 'font-bold text-gray-900' : 'font-semibold text-gray-800' }}">
            ৳{{ number_format($total, 2) }}
        </span>
        @if ($prev !== null)
            @php $diff = $total - $prev; @endphp
            <div class="text-xs {{ $diff >= 0 ? 'text-green-600' : 'text-red-500' }}">
                {{ $diff >= 0 ? '↑' : '↓' }} ৳{{ number_format(abs($diff), 0) }}
            </div>
        @endif
    </div>
</div>
