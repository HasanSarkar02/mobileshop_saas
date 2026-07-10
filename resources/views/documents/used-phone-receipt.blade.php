@php
    $shop = $acquisition->branch->shop ?? auth()->user()?->shop;
    $signatories = [
        ['title' => 'Seller Signature', 'name' => ''],
        ['title' => 'Seller NID Confirmed', 'name' => ''],
        ['title' => 'Purchased By', 'name' => $acquisition->createdBy?->name ?? ''],
    ];
@endphp

<x-document.layout title="Used Phone Purchase Receipt" :docNumber="$acquisition->acquisition_number" :shop="$shop" :branch="$acquisition->branch"
    :exportPdfUrl="route('documents.used-phone.pdf', $acquisition)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Purchase Date', 'value' => $acquisition->created_at->format('d M Y')],
        ['label' => 'Branch', 'value' => $acquisition->branch?->name],
        ['label' => 'Condition', 'value' => $acquisition->condition->label()],
        ['label' => 'Reference', 'value' => $acquisition->acquisition_number],
    ]" />

    <x-document.parties :from="[
        'title' => 'Purchased From (Seller)',
        'name' => $acquisition->seller_name,
        'lines' => [
            $acquisition->seller_phone ? 'Phone: ' . $acquisition->seller_phone : null,
            $acquisition->seller_nid ? 'NID: ' . $acquisition->seller_nid : null,
            $acquisition->seller_address,
        ],
    ]" :to="[
        'title' => 'Purchased By',
        'name' => $shop?->name,
        'lines' => [
            $acquisition->branch?->name ? 'Branch: ' . $acquisition->branch->name : null,
            'Received by: ' . ($acquisition->createdBy?->name ?? ''),
        ],
    ]" />

    {{-- Phone Details --}}
    <div class="doc-section-title">Device Details</div>
    <div style="border:2pt solid #1e3a5f;padding:4mm;margin-bottom:3mm;">
        <div style="font-size:13pt;font-weight:700;color:#1e3a5f;margin-bottom:2mm;">
            {{ $acquisition->model_description }}</div>
        <div class="doc-two-col">
            <div>
                <div class="doc-kv-row"><span class="doc-kv-label">IMEI 1</span><span
                        class="doc-kv-value doc-mono">{{ $acquisition->imei_1 }}</span></div>
                @if ($acquisition->imei_2)
                    <div class="doc-kv-row"><span class="doc-kv-label">IMEI 2</span><span
                            class="doc-kv-value doc-mono">{{ $acquisition->imei_2 }}</span></div>
                @endif
                <div class="doc-kv-row"><span class="doc-kv-label">Condition</span><span
                        class="doc-kv-value">{{ $acquisition->condition->label() }}</span></div>
            </div>
            <div>
                @if ($acquisition->accessories)
                    <div class="doc-kv-row"><span class="doc-kv-label">Accessories</span><span
                            class="doc-kv-value">{{ $acquisition->accessories }}</span></div>
                @endif
                @if ($acquisition->condition_notes)
                    <div class="doc-kv-row"><span class="doc-kv-label">Notes</span><span
                            class="doc-kv-value">{{ $acquisition->condition_notes }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payment --}}
    <div
        style="border:2pt solid #1e3a5f;padding:4mm 6mm;display:flex;justify-content:space-between;align-items:center;margin-bottom:3mm;">
        <div>
            <div style="font-size:7pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;">Amount Paid to
                Seller</div>
            <div style="font-size:18pt;font-weight:700;color:#1e3a5f;font-family:var(--doc-mono);">
                ৳{{ number_format($acquisition->purchase_price, 2) }}</div>
            <div style="font-size:8pt;color:#374151;">Via: {{ $acquisition->paymentAccount?->name }}</div>
        </div>
        <div class="doc-stamp doc-stamp-paid" style="position:static;transform:rotate(-8deg);">PAID</div>
    </div>

    <div class="doc-amount-words">
        <strong>Amount Paid:</strong> Taka {{ number_format($acquisition->purchase_price, 2) }} Only
    </div>

    <div class="doc-notes" style="margin-top:2mm;">
        The seller confirms that the device is their personal property and they have the full legal right to sell it.
        The seller is responsible for any claims arising from unauthorized sale, theft, or third-party ownership
        disputes.
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
