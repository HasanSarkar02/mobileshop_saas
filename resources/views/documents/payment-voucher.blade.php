{{--
  Payment Voucher — used when shop pays money OUT:
  Supplier payment, expense payment, salary, advance, etc.
--}}
@php
    $shop = $voucher['shop'];
@endphp

<x-document.layout title="Payment Voucher" :docNumber="$voucher['voucher_number']" :shop="$shop" :exportPdfUrl="$voucher['pdf_url'] ?? null">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Voucher Date', 'value' => $voucher['date']],
        ['label' => 'Payment Mode', 'value' => $voucher['payment_mode']],
        ['label' => 'Account', 'value' => $voucher['account']],
        ['label' => 'Reference', 'value' => $voucher['reference'] ?? 'N/A'],
    ]" />

    <div class="doc-section-title">Payment Details</div>

    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Paid To</span><span
                    class="doc-kv-value">{{ $voucher['payee'] }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Purpose</span><span
                    class="doc-kv-value">{{ $voucher['purpose'] }}</span></div>
            @if (isset($voucher['category']))
                <div class="doc-kv-row"><span class="doc-kv-label">Category</span><span
                        class="doc-kv-value">{{ $voucher['category'] }}</span></div>
            @endif
        </div>
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Account Debited</span><span
                    class="doc-kv-value">{{ $voucher['account'] }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">GL Code</span><span
                    class="doc-kv-value doc-mono">{{ $voucher['gl_code'] ?? '—' }}</span></div>
        </div>
    </div>

    {{-- Amount Box --}}
    <div
        style="border:2pt solid #1e3a5f;padding:4mm 6mm;display:flex;justify-content:space-between;align-items:center;margin-bottom:3mm;">
        <div>
            <div style="font-size:7pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;">Amount Paid</div>
            <div style="font-size:18pt;font-weight:700;color:#1e3a5f;font-family:var(--doc-mono);">
                ৳{{ number_format($voucher['amount'], 2) }}</div>
        </div>
        <div class="doc-stamp doc-stamp-paid" style="position:static;transform:rotate(-8deg);">PAID</div>
    </div>

    <div class="doc-amount-words">
        <strong>Amount in Words:</strong> Taka {{ number_format($voucher['amount'], 2) }} Only
    </div>

    @if (!empty($voucher['notes']))
        <div class="doc-notes"><span class="doc-notes-label">Notes</span>{{ $voucher['notes'] }}</div>
    @endif

    <x-document.signatures :signatories="[
        ['title' => 'Received By', 'name' => ''],
        ['title' => 'Prepared By', 'name' => $voucher['prepared_by'] ?? ''],
        ['title' => 'Approved By', 'name' => ''],
    ]" />

</x-document.layout>
