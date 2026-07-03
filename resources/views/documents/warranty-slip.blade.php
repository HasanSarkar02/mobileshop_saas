@php
    $shop = $unit->shop ?? auth()->user()?->shop;
    $saleRec = $sale ?? ($unit->saleRecord ?? null);
    $mfrExp = $unit->warrantyExpiresAt();
    $shopExp = $unit->shopWarrantyExpiresAt();
@endphp

<x-document.layout title="Warranty Certificate" :docNumber="'WR-' . $unit->serial_number" :shop="$shop">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Purchase Date', 'value' => $saleRec?->confirmed_at?->format('d M Y')],
        ['label' => 'Invoice No.', 'value' => $saleRec?->sale_number],
        ['label' => 'Customer', 'value' => $saleRec?->customer?->name ?? 'Walk-in'],
        ['label' => 'Issued On', 'value' => now()->format('d M Y')],
    ]" />

    {{-- Product Details --}}
    <div style="border:2pt solid #1e3a5f;padding:5mm;margin-bottom:4mm;text-align:center;">
        <div style="font-size:8pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:2mm;">
            Product Details</div>
        <div style="font-size:14pt;font-weight:700;color:#1e3a5f;">
            {{ $unit->variant?->product?->name }}
            @if ($unit->variant?->attributes_label)
                — {{ $unit->variant->attributes_label }}
            @endif
        </div>
        <div class="doc-mono" style="font-size:11pt;margin-top:2mm;color:#374151;">
            IMEI: {{ $unit->serial_number }}
            @if ($unit->secondary_serial_number)
                / {{ $unit->secondary_serial_number }}
            @endif
        </div>
    </div>

    {{-- Warranty Coverage --}}
    <div class="doc-section-title">Warranty Coverage</div>
    <div class="doc-two-col" style="margin-bottom:4mm;">
        @if ($unit->manufacturer_warranty_months > 0)
            <div class="doc-party">
                <div class="doc-party-header">Manufacturer Warranty</div>
                <div class="doc-party-body">
                    <div class="doc-kv-row"><span class="doc-kv-label">Duration</span><span
                            class="doc-kv-value">{{ $unit->manufacturer_warranty_months }} Months</span></div>
                    @if ($mfrExp)
                        <div class="doc-kv-row"><span class="doc-kv-label">Valid Until</span><span class="doc-kv-value"
                                style="color:{{ $mfrExp->isFuture() ? '#16a34a' : '#dc2626' }}">{{ $mfrExp->format('d M Y') }}</span>
                        </div>
                        <div class="doc-kv-row"><span class="doc-kv-label">Status</span><span
                                class="doc-kv-value">{{ $mfrExp->isFuture() ? '✓ Active' : '✗ Expired' }}</span></div>
                    @endif
                </div>
            </div>
        @endif
        @if ($unit->shop_warranty_days > 0)
            <div class="doc-party">
                <div class="doc-party-header">Shop Warranty</div>
                <div class="doc-party-body">
                    <div class="doc-kv-row"><span class="doc-kv-label">Duration</span><span
                            class="doc-kv-value">{{ $unit->shop_warranty_days }} Days</span></div>
                    @if ($shopExp)
                        <div class="doc-kv-row"><span class="doc-kv-label">Valid Until</span><span class="doc-kv-value"
                                style="color:{{ $shopExp->isFuture() ? '#16a34a' : '#dc2626' }}">{{ $shopExp->format('d M Y') }}</span>
                        </div>
                        <div class="doc-kv-row"><span class="doc-kv-label">Status</span><span
                                class="doc-kv-value">{{ $shopExp->isFuture() ? '✓ Active' : '✗ Expired' }}</span></div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="doc-notes">
        <span class="doc-notes-label">Terms & Conditions</span>
        • Warranty covers manufacturing defects only. Physical damage, water damage, and unauthorized repair voids
        warranty.
        • Original invoice and warranty slip must be presented for any warranty claim.
        • Software issues and accessories are not covered under warranty.
    </div>

    <x-document.signatures :signatories="[['title' => 'Shop Seal & Signature', 'name' => ''], ['title' => 'Customer Signature', 'name' => '']]" />

</x-document.layout>
