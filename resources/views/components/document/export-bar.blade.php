@props([
    'title' => '',
    'printUrl' => null,
    'pdfUrl' => null,
    'excelUrl' => null,
    'csvUrl' => null,
])

<div class="no-print flex items-center gap-2 p-3 bg-white border border-gray-200 rounded-xl shadow-sm mb-4">
    @if ($title)
        <span class="text-sm font-semibold text-gray-700 flex-1 truncate">{{ $title }}</span>
    @else
        <span class="flex-1"></span>
    @endif

    @if ($printUrl)
        <a href="{{ $printUrl }}" target="_blank" class="btn-secondary btn-sm whitespace-nowrap">
            🖨 Print
        </a>
    @else
        <button onclick="window.print()" class="btn-secondary btn-sm whitespace-nowrap">
            🖨 Print
        </button>
    @endif

    @if ($pdfUrl)
        <a href="{{ $pdfUrl }}" target="_blank" class="btn-secondary btn-sm whitespace-nowrap">
            📄 PDF
        </a>
    @endif

    @if ($excelUrl)
        <a href="{{ $excelUrl }}" class="btn-secondary btn-sm whitespace-nowrap">
            📊 Excel
        </a>
    @endif

    @if ($csvUrl)
        <a href="{{ $csvUrl }}" class="btn-secondary btn-sm whitespace-nowrap">
            📋 CSV
        </a>
    @endif
</div>
