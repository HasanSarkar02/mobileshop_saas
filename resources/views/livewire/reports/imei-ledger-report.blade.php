<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">IMEI Ledger</h2>
        <span class="text-xs text-gray-400">Full lifecycle of every serialized unit ever received</span>
        <x-document.export-bar title="IMEI Ledger" :printUrl="route('reports.imei-ledger.print', ['branch' => $branchId, 'status' => $unitStatus, 'q' => $search])" />
    </div>

    {{-- Filters --}}
    <div class="card p-4 space-y-3">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="label text-xs">IMEI / Serial Search</label>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search IMEI number…"
                    class="input w-52 text-sm">
            </div>
            <div>
                <label class="label text-xs">Status</label>
                <select wire:model.live="unitStatus" class="input text-sm w-36">
                    <option value="">All Statuses</option>
                    @foreach (\App\Enums\UnitStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ ucfirst(str_replace('_', ' ', $s->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label text-xs">Brand</label>
                <select wire:model.live="brandId" class="input text-sm w-36">
                    <option value="0">All Brands</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($this->getBranchesProperty()->count() > 1)
                <div>
                    <label class="label text-xs">Branch</label>
                    <select wire:model.live="branchId" class="input text-sm w-36">
                        <option value="0">All Branches</option>
                        @foreach ($this->getBranchesProperty() as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Status count chips --}}
        @php $counts = $this->imeiCounts->keyBy('status'); @endphp
        <div class="flex flex-wrap gap-2">
            @foreach ([['status' => 'in_stock', 'label' => 'In Stock', 'color' => 'green'], ['status' => 'sold', 'label' => 'Sold', 'color' => 'gray'], ['status' => 'damaged', 'label' => 'Damaged', 'color' => 'red'], ['status' => 'rma_to_supplier', 'label' => 'RMA', 'color' => 'blue']] as $chip)
                <span class="badge badge-{{ $chip['color'] }} cursor-pointer"
                    wire:click="$set('unitStatus', '{{ $chip['status'] }}')">
                    {{ $chip['label'] }}: {{ $counts[$chip['status']]->count ?? 0 }}
                </span>
            @endforeach
            @if ($unitStatus)
                <button wire:click="$set('unitStatus', '')" class="text-xs text-gray-400 hover:text-gray-600">
                    Clear × All: {{ $this->imeiCounts->sum('count') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">IMEI / Serial</th>
                        <th class="table-th">Product</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Cost</th>
                        <th class="table-th">Received</th>
                        <th class="table-th">Sale Info</th>
                        <th class="table-th">Warranty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $row)
                        @php
                            $statusColors = [
                                'in_stock' => 'badge-green',
                                'sold' => 'badge-gray',
                                'damaged' => 'badge-red',
                                'lost' => 'badge-red',
                                'reserved' => 'badge-yellow',
                                'rma_to_supplier' => 'badge-blue',
                                'returned_pending_inspection' => 'badge-yellow',
                                'written_off' => 'badge-gray',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-mono font-semibold text-gray-900 text-sm">
                                    {{ $row->serial_number }}
                                </div>
                                @if ($row->secondary_serial_number)
                                    <div class="font-mono text-xs text-gray-400">{{ $row->secondary_serial_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $row->product_name }}</div>
                                <div class="text-xs text-gray-400">{{ $row->brand }} · {{ $row->sku }}</div>
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $statusColors[$row->status] ?? 'badge-gray' }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $row->status)) }}
                                </span>
                            </td>
                            <td class="table-td text-xs text-gray-500">{{ $row->branch_name ?? '—' }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($row->cost_price, 2) }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($row->received_at)->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                @if ($row->sale_number)
                                    <div class="text-xs">
                                        <a href="{{ route('sales.show', \App\Models\Sale::where('sale_number', $row->sale_number)->value('id')) }}"
                                            wire:navigate
                                            class="font-mono text-indigo-600 hover:underline font-semibold">
                                            {{ $row->sale_number }}
                                        </a>
                                        <div class="text-gray-500">{{ $row->customer_name ?? 'Walk-in' }}</div>
                                        <div class="text-gray-400">৳{{ number_format($row->sale_amount, 0) }}</div>
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="table-td text-xs">
                                @if ($row->manufacturer_warranty_months > 0)
                                    <div class="text-gray-600">Mfr: {{ $row->manufacturer_warranty_months }}mo</div>
                                @endif
                                @if ($row->shop_warranty_days > 0)
                                    <div class="text-indigo-500">Shop: {{ $row->shop_warranty_days }}d</div>
                                @endif
                                @if (!$row->manufacturer_warranty_months && !$row->shop_warranty_days)
                                    <span class="text-gray-300">None</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-td text-center text-gray-400 py-10">
                                No IMEI records found.
                                @if ($search)
                                    Try a different search term.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $records->links() }}</div>
        @endif
    </div>
</div>
