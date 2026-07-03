@props([
    'title' => 'Document',
    'subtitle' => null,
    'docNumber' => null,
    'shop' => null,
    'branch' => null,
    'landscape' => false,
    'showExportBar' => true,
    'exportPdfUrl' => null,
    'exportCsvUrl' => null,
    'exportXlsUrl' => null,
    'confidential' => null, // null = use shop setting, true/false to override
])

@php
    $shop = $shop ?? auth()->user()?->shop;
    $branch = $branch ?? null;
    $isConf = $confidential ?? ($shop?->show_document_confidential ?? false);
    $logoUrl = $shop?->logo_path ? Storage::url($shop->logo_path) : null;
    $generatedAt = now()->format('d M Y, H:i:s');
    $generatedBy = auth()->user()?->name ?? 'System';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — {{ $shop?->name ?? 'Document' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    @vite(['resources/css/document.css'])
    @if (!empty($styles))
        <style>
            {{ $styles }}
        </style>
    @endif
</head>

<body class="doc-body {{ $landscape ? 'doc-landscape' : '' }}">

    {{-- ── Export Bar (screen only, hidden on print) ── --}}
    @if ($showExportBar)
        <div class="doc-export-bar no-print">
            <span class="doc-export-bar-title">{{ $title }} @if ($docNumber)
                    — {{ $docNumber }}
                @endif
            </span>
            <button onclick="window.print()" class="doc-export-btn doc-export-btn-primary">
                🖨 Print
            </button>
            @if ($exportPdfUrl)
                <a href="{{ $exportPdfUrl }}" class="doc-export-btn">📄 PDF</a>
            @else
                <button onclick="window.print()" class="doc-export-btn">📄 Save as PDF</button>
            @endif
            @if ($exportXlsUrl)
                <a href="{{ $exportXlsUrl }}" class="doc-export-btn">📊 Excel</a>
            @endif
            @if ($exportCsvUrl)
                <a href="{{ $exportCsvUrl }}" class="doc-export-btn">📋 CSV</a>
            @endif
            <button onclick="window.close()" class="doc-export-btn" style="opacity:0.6">✕ Close</button>
        </div>
    @endif

    {{-- ── Document Page ── --}}
    <div class="doc-page" style="position:relative;">

        {{-- ── HEADER ── --}}
        <div class="doc-header">
            <div class="doc-header-left">
                {{-- Logo --}}
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $shop?->name }}" class="doc-logo">
                @else
                    <div class="doc-logo-placeholder">
                        <svg width="20" height="20" fill="none" stroke="white" stroke-width="1.5"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                @endif

                {{-- Company Info --}}
                <div>
                    <div class="doc-company-name">{{ $shop?->name ?? 'Company Name' }}</div>
                    <div class="doc-company-meta">
                        @if ($branch && $branch->name !== $shop?->name)
                            <strong>Branch:</strong> {{ $branch->name }}
                            @if ($branch->address)
                                · {{ $branch->address }}
                            @endif
                            <br>
                        @endif
                        @if ($shop?->address)
                            {{ $shop->address }}<br>
                        @endif
                        @if ($shop?->phone) <strong>Phone:</strong>
                            {{ $shop->phone }}@if ($shop->email)
                                &nbsp;·&nbsp;
                            @endif
                        @endif
                        @if ($shop?->email)
                            <strong>Email:</strong> {{ $shop->email }}
                        @endif
                        @if ($shop?->phone || $shop?->email)
                            <br>
                        @endif
                        @if ($shop?->vat_enabled && $shop->vat_registration_number)
                            <strong>VAT Reg / BIN:</strong> {{ $shop->vat_registration_number }}&nbsp;·&nbsp;
                        @endif
                        @if ($shop?->trade_license_number)
                            <strong>Trade Lic:</strong> {{ $shop->trade_license_number }}
                        @endif
                        @if ($shop?->website)
                            @if ($shop->vat_registration_number || $shop->trade_license_number)
                                <br>
                            @endif
                            {{ $shop->website }}
                        @endif
                    </div>
                </div>
            </div>

            {{-- Document Title --}}
            <div class="doc-header-right">
                <div class="doc-title">{{ $title }}</div>
                @if ($subtitle)
                    <div class="doc-subtitle">{{ $subtitle }}</div>
                @endif
                @if ($docNumber)
                    <div class="doc-number">{{ $docNumber }}</div>
                @endif
            </div>
        </div>

        {{-- ── CONFIDENTIAL STAMP ── --}}
        @if ($isConf)
            <div class="doc-stamp-container no-page-break">
                <div class="doc-stamp doc-stamp-partial">CONFIDENTIAL</div>
            </div>
        @endif

        {{-- ── DOCUMENT BODY ── --}}
        {{ $slot }}

        {{-- ── FOOTER ── --}}
        <div class="doc-footer">
            <div>
                @if ($shop?->document_footer_note)
                    {{ $shop->document_footer_note }}
                @else
                    Generated by {{ $shop?->name ?? 'ERP System' }}
                @endif
            </div>
            <div class="doc-footer-center">
                @if ($isConf)
                    <span class="doc-confidential">Confidential</span>
                @endif
            </div>
            <div style="text-align:right;">
                Printed: {{ $generatedAt }} · By: {{ $generatedBy }}<br>
                <span style="font-size:6pt;">This is a computer-generated document.</span>
            </div>
        </div>

    </div>

    <script>
        // Auto-print if ?autoprint=1
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.onload = () => setTimeout(() => window.print(), 400);
        }

        // PDF hint message
        document.querySelectorAll('[onclick="window.print()"]').forEach(btn => {
            if (btn.textContent.includes('PDF')) {
                btn.addEventListener('click', () => {
                    if (!btn.href) {
                        alert('Tip: In the print dialog, select "Save as PDF" as the destination.');
                    }
                });
            }
        });
    </script>

</body>

</html>
