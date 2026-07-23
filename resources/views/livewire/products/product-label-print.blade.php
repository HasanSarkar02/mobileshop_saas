<div class="max-w-5xl mx-auto space-y-5 print:max-w-none">

    @php
        $barcodeOptions = match ($labelSize) {
            'small' => ['width' => 1.0, 'height' => 18],
            'large' => ['width' => 1.6, 'height' => 34],
            default => ['width' => 1.3, 'height' => 26], // medium
        };
    @endphp
    {{-- Controls — hidden on print --}}
    <div class="card p-5 print:hidden space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Print Barcode Labels</h2>
            <button onclick="window.print()" class="btn-primary btn-sm">🖨 Print Labels</button>
        </div>

        <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600">Label Size:</label>
            <select wire:model.live="labelSize" class="input w-auto text-sm">
                <option value="small">Small (25mm × 15mm)</option>
                <option value="medium">Medium (40mm × 25mm)</option>
                <option value="large">Large (50mm × 30mm)</option>
            </select>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach ($variants as $v)
                <div class="flex items-center gap-4 py-2">
                    <div class="flex-1">
                        <div class="text-sm font-medium">{{ $v->product->name }}
                            {{ $v->attributes_label ? '— ' . $v->attributes_label : '' }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ $v->barcode }}</div>
                    </div>
                    <input type="number" min="1" max="500" wire:model.live="selections.{{ $v->id }}"
                        class="input w-20 text-sm" placeholder="Qty">
                    <button wire:click="removeVariant({{ $v->id }})"
                        class="text-gray-300 hover:text-red-500 text-sm">✕</button>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Printable label grid --}}
    <div class="label-sheet label-{{ $labelSize }}" id="label-sheet">
        @foreach ($variants as $v)
            @for ($i = 0; $i < (int) ($selections[$v->id] ?? 1); $i++)
                <div class="label-cell" wire:key="label-{{ $v->id }}-{{ $i }}">
                    <div class="label-name">{{ \Illuminate\Support\Str::limit($v->product->name, 24) }}</div>
                    @if ($v->attributes_label)
                        <div class="label-variant">{{ $v->attributes_label }}</div>
                    @endif
                    <svg class="label-barcode" wire:ignore data-barcode="{{ $v->barcode }}"
                        data-bar-width="{{ $barcodeOptions['width'] }}"
                        data-bar-height="{{ $barcodeOptions['height'] }}">
                    </svg>
                    <div class="label-code">{{ $v->barcode }}</div>
                    <div class="label-price">৳{{ number_format($v->selling_price, 2) }}</div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        (function() {
            function renderAllBarcodes() {
                document.querySelectorAll('#label-sheet svg.label-barcode[data-barcode]').forEach(function(svg) {
                    if (svg.dataset.rendered === '1') return;
                    var value = svg.getAttribute('data-barcode');
                    if (!value) return;
                    var barWidth = parseFloat(svg.getAttribute('data-bar-width')) || 1.3;
                    var barHeight = parseFloat(svg.getAttribute('data-bar-height')) || 26;
                    try {
                        window.JsBarcode(svg, value, {
                            format: 'CODE128',
                            displayValue: false,
                            margin: 0,
                            width: barWidth,
                            height: barHeight,
                        });
                        // Strip the fixed pixel size JsBarcode wrote, so our CSS
                        // (width: 100%; height: auto) is what actually controls
                        // the rendered size inside the label cell.
                        svg.removeAttribute('width');
                        svg.removeAttribute('height');
                        svg.dataset.rendered = '1';
                    } catch (e) {
                        console.error('Barcode render failed for value:', value, e);
                    }
                });
            }

            function ensureJsBarcodeLoaded(callback, attempt) {
                attempt = attempt || 0;
                if (typeof window.JsBarcode === 'function') {
                    callback();
                    return;
                }
                // Bundle may still be finishing initialization — retry briefly before assuming it's missing.
                if (attempt < 15) {
                    setTimeout(function() {
                        ensureJsBarcodeLoaded(callback, attempt + 1);
                    }, 100);
                    return;
                }
                var existing = document.getElementById('jsbarcode-cdn-fallback');
                if (existing) {
                    existing.addEventListener('load', callback);
                    return;
                }
                console.warn('JsBarcode not found on window after waiting 1.5s — falling back to CDN.');
                var script = document.createElement('script');
                script.id = 'jsbarcode-cdn-fallback';
                script.src = 'https://cdn.jsdelivr.net/npm/jsbarcode/dist/JsBarcode.all.min.js';
                script.onload = function() {
                    console.log('JsBarcode loaded via CDN fallback.');
                    callback();
                };
                script.onerror = function() {
                    console.error('CDN fallback also failed — check internet connection or the npm build.');
                };
                document.head.appendChild(script);
            }

            function whenDocumentReady(fn) {
                // Handles both a fresh full page load AND a wire:navigate SPA
                // re-run of this same script, where DOMContentLoaded already
                // fired once and will never fire again in this document.
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    fn();
                }
            }

            whenDocumentReady(function() {
                ensureJsBarcodeLoaded(renderAllBarcodes);

                var sheet = document.getElementById('label-sheet');
                if (sheet && window.MutationObserver) {
                    new MutationObserver(function() {
                        ensureJsBarcodeLoaded(renderAllBarcodes);
                    }).observe(sheet, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        })();
    </script>
    <style>
        .label-sheet {
            display: grid;
            gap: 3mm;
        }

        .label-small {
            grid-template-columns: repeat(6, 25mm);
        }

        .label-medium {
            grid-template-columns: repeat(4, 40mm);
        }

        .label-large {
            grid-template-columns: repeat(3, 50mm);
        }

        .label-cell {
            border: 1px dashed #ccc;
            padding: 2mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .label-name {
            font-size: 7pt;
            font-weight: 600;
        }

        .label-variant {
            font-size: 6pt;
            color: #666;
        }

        .label-code {
            font-size: 6pt;
            font-family: monospace;
        }

        .label-price {
            font-size: 7pt;
            font-weight: 700;
        }

        @media print {
            .label-cell {
                border: none;
            }

            @page {
                margin: 5mm;
            }
        }

        .label-barcode {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
