@php
    $shop = auth()->user()?->shop;
    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp
<x-document.layout title="Customer Due Report" :subtitle="'As of ' . $periodLabel" :shop="$shop">

    <div style="margin-bottom: 20px;">
        <div
            style="display: flex; gap: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div>
                <div style="font-size: 12px; color: #64748b;">Total Customers</div>
                <div style="font-size: 18px; font-weight: 700; color: #0f172a;">
                    {{ $customerStats['total_customers'] ?? 0 }}</div>
            </div>
            <div style="width: 1px; background: #cbd5e1;"></div>
            <div>
                <div style="font-size: 12px; color: #64748b;">Total Outstanding</div>
                <div style="font-size: 18px; font-weight: 700; color: #dc2626;">
                    ৳{{ number_format($customerStats['total_due'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <table class="doc-table">
        <thead>
            <tr style="background: #1e40af; color: white;">
                <th style="text-align: left; padding: 8px;">Customer</th>
                <th style="text-align: left; padding: 8px;">Phone</th>
                <th class="right" style="padding: 8px;">Current</th>
                <th class="right" style="padding: 8px;">1-30 Days</th>
                <th class="right" style="padding: 8px;">31-60 Days</th>
                <th class="right" style="padding: 8px;">Over 60</th>
                <th class="right" style="padding: 8px;">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customerDues as $due)
                <tr>
                    <td>{{ $due->name }}</td>
                    <td>{{ $due->phone }}</td>
                    <td class="right">{{ number_format($due->current, 2) }}</td>
                    <td class="right">{{ number_format($due->days_1_30, 2) }}</td>
                    <td class="right">{{ number_format($due->days_31_60, 2) }}</td>
                    <td class="right">{{ number_format($due->over_60, 2) }}</td>
                    <td class="right" style="font-weight: 700;">{{ number_format($due->total_due, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="6" style="text-align: right;">Total Outstanding</td>
                <td class="right">৳{{ number_format($customerStats['total_due'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <x-document.signatures :signatories="$signatories" />
</x-document.layout>
