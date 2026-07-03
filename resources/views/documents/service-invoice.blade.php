@php
    $shop = $ticket->shop ?? auth()->user()?->shop;
    $branch = $ticket->branch;
    $isPaid = $ticket->amount_due <= 0;
@endphp

<x-document.layout title="Service Invoice" :subtitle="$ticket->is_warranty_service ? 'WARRANTY SERVICE — No Charge' : null" :docNumber="$ticket->ticket_number" :shop="$shop" :branch="$branch"
    :exportPdfUrl="route('documents.service-invoice.pdf', $ticket)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Received Date', 'value' => $ticket->received_at?->format('d M Y')],
        ['label' => 'Delivered Date', 'value' => $ticket->delivered_at?->format('d M Y') ?? 'Pending'],
        ['label' => 'Technician', 'value' => $ticket->technician?->name ?? 'Unassigned'],
        ['label' => 'Status', 'value' => $ticket->status->label()],
    ]" />

    <x-document.parties :to="[
        'title' => 'Customer',
        'name' => $ticket->customer_name,
        'lines' => [$ticket->customer_phone, $ticket->customer?->address],
    ]" :extra="[
        'title' => 'Device Information',
        'lines' => [
            ($ticket->device_brand ? $ticket->device_brand . ' ' : '') . ($ticket->device_model ?? ''),
            $ticket->device_imei ? 'IMEI: ' . $ticket->device_imei : null,
            $ticket->device_color ? 'Color: ' . $ticket->device_color : null,
            $ticket->device_condition ? 'Condition: ' . $ticket->device_condition : null,
        ],
    ]" />

    {{-- Problem & Diagnosis --}}
    <div class="doc-notes">
        <span class="doc-notes-label">Problem Reported</span>
        {{ $ticket->problem_description }}
    </div>
    @if ($ticket->diagnosis_notes)
        <div class="doc-notes" style="margin-top:2mm;">
            <span class="doc-notes-label">Technician Diagnosis</span>
            {{ $ticket->diagnosis_notes }}
        </div>
    @endif

    {{-- Parts --}}
    @if ($ticket->parts->isNotEmpty())
        <div class="doc-section-title">Parts & Materials Used</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Part Description</th>
                    <th class="center" style="width:10%">Qty</th>
                    <th class="right" style="width:18%">Unit Cost</th>
                    <th class="right" style="width:18%">Total</th>
                    <th style="width:12%">Source</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ticket->parts as $i => $part)
                    <tr>
                        <td class="center muted">{{ $i + 1 }}</td>
                        <td>{{ $part->part_description }}</td>
                        <td class="center">{{ $part->quantity }}</td>
                        <td class="right mono">৳{{ number_format($part->unit_cost, 2) }}</td>
                        <td class="right mono">৳{{ number_format($part->line_total, 2) }}</td>
                        <td class="muted">{{ $part->from_inventory ? 'Stock' : 'External' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Charges --}}
    <div class="doc-totals">
        <div class="doc-totals-table">
            @if ($ticket->parts_cost > 0)
                <div class="doc-totals-row">
                    <span class="label">Parts Cost</span>
                    <span class="amount">৳{{ number_format($ticket->parts_cost, 2) }}</span>
                </div>
            @endif
            <div class="doc-totals-row">
                <span class="label">Labour Charge</span>
                <span class="amount">৳{{ number_format($ticket->labor_charge, 2) }}</span>
            </div>
            <div class="doc-totals-row grand">
                <span class="label">{{ $ticket->is_warranty_service ? 'TOTAL (WAIVED)' : 'TOTAL CHARGE' }}</span>
                <span
                    class="amount">{{ $ticket->is_warranty_service ? 'NIL' : '৳' . number_format($ticket->total_charge, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    @if ($ticket->payments->isNotEmpty())
        <div class="doc-section-title">Payment History</div>
        @foreach ($ticket->payments as $pmt)
            <div class="doc-kv-row">
                <span class="doc-kv-label">{{ $pmt->payment_date->format('d M Y') }} via
                    {{ $pmt->paymentAccount?->name }}</span>
                <span class="doc-kv-value doc-text-green">৳{{ number_format($pmt->amount, 2) }}</span>
            </div>
        @endforeach
        @if ($ticket->amount_due > 0)
            <div class="doc-kv-row" style="border-top:1pt solid #dc2626;margin-top:1mm;padding-top:1mm;">
                <span class="doc-kv-label doc-text-red">Balance Due</span>
                <span class="doc-kv-value doc-text-red">৳{{ number_format($ticket->amount_due, 2) }}</span>
            </div>
        @endif
    @endif

    @if ($isPaid)
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-paid">PAID</div>
        </div>
    @elseif($ticket->amount_due > 0)
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-partial">DUE: ৳{{ number_format($ticket->amount_due, 0) }}</div>
        </div>
    @endif

    <x-document.signatures :signatories="[
        ['title' => 'Technician', 'name' => $ticket->technician?->name ?? ''],
        ['title' => 'Customer Received', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ]" />

    <div class="doc-notes" style="margin-top:3mm;">
        <strong>Warranty on Repair:</strong> 7 days on labor. Parts warranty as per manufacturer.
        The company is not liable for data loss during repair.
    </div>

</x-document.layout>
