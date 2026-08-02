<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Opening Stock Entry</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Scan your existing physical inventory in one continuous session.
                    Each line posts:
                    <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                        Dr Inventory (1200) / Cr Opening Balance Equity (3020)
                    </span>
                </p>
            </div>
        </div>
        <div class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
            ⚠ <strong>One-time operation.</strong>
            Use this only for stock that existed before this system was set up.
            All future stock must come through <strong>Purchases</strong>.
        </div>
    </div>

    {{-- Branch (once per session) --}}
    <div class="card p-5">
        <label class="label">Branch (applies to this whole scanning session) *</label>
        <select wire:model="branchId" class="input max-w-xs">
            <option value="0">Select branch…</option>
            @foreach ($this->branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
        @if ($branchId < 1)
            <p class="text-xs text-amber-600 mt-1">Select a branch to enable scanning below.</p>
        @endif
    </div>

    {{-- Scan panel --}}
    <div class="card p-5 space-y-3 border-2 border-indigo-200" x-data="{
        scanning: false,
        videoStream: null,
        detector: null,
        lastCode: null,
        lastScanAt: 0,
    
        beep(ok) {
            try {
                const ctx = new(window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.frequency.value = ok ? 1200 : 300;
                osc.connect(gain);
                gain.connect(ctx.destination);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                osc.start();
                osc.stop(ctx.currentTime + (ok ? 0.08 : 0.18));
            } catch (e) {}
            if (navigator.vibrate) navigator.vibrate(ok ? 40 : [40, 60, 40]);
        },
    
        async startScan() {
            if (this.scanning) return;
            if (!('BarcodeDetector' in window)) {
                $dispatch('notify', { type: 'error', message: 'Camera scanning isn\'t supported in this browser (works on Chrome/Edge desktop & Android — not Safari/iOS). Use a USB scanner or type manually.' });
                return;
            }
            if (!window.isSecureContext) {
                $dispatch('notify', { type: 'error', message: 'Camera access needs HTTPS.' });
                return;
            }
            this.detector = new BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'qr_code', 'data_matrix']
            });
            try {
                this.videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.$refs.stockScanVideo.srcObject = this.videoStream;
                await this.$refs.stockScanVideo.play();
                this.scanning = true;
                this.loop();
            } catch (e) {
                const msg = e.name === 'NotAllowedError' ? 'Camera permission denied. Allow camera access and try again.' :
                    (e.name === 'NotFoundError' ? 'No camera found on this device.' : 'Could not start camera: ' + e.message);
                $dispatch('notify', { type: 'error', message: msg });
            }
        },
    
        async loop() {
            if (!this.scanning) return;
            try {
                const found = await this.detector.detect(this.$refs.stockScanVideo);
                if (found.length > 0) {
                    const val = found[0].rawValue;
                    const now = Date.now();
                    if (val !== this.lastCode || (now - this.lastScanAt) > 1300) {
                        this.lastCode = val;
                        this.lastScanAt = now;
                        $wire.barcodeInput = val;
                        $wire.processBarcode();
                    }
                }
            } catch (e) {}
            if (this.scanning) requestAnimationFrame(() => this.loop());
        },
    
        stopScan() {
            this.scanning = false;
            this.videoStream?.getTracks().forEach(t => t.stop());
            this.videoStream = null;
        }
    }"
        @keydown.f2.window.prevent="$refs.barcodeManualInput?.focus()"
        @scan-feedback.window="beep($event.detail.ok); $refs.barcodeManualInput?.focus()">

        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Scan Products</h3>
            <span class="text-xs text-gray-400">F2 = focus scan field</span>
        </div>

        <div class="flex items-center gap-2">
            <input x-ref="barcodeManualInput" wire:model="barcodeInput" wire:keydown.enter="processBarcode"
                type="text" autofocus @disabled($branchId < 1)
                placeholder="Scan product barcode / SKU / IMEI, or click here and use a USB scanner…"
                class="input pl-4 text-base font-mono disabled:bg-gray-50" autocomplete="off">
            <button type="button" wire:click="processBarcode" @disabled($branchId < 1)
                class="btn-primary shrink-0">Add</button>
            <button type="button" @click="startScan()" x-show="!scanning" @disabled($branchId < 1)
                class="btn-secondary shrink-0" title="Scan with camera">📷 Camera</button>
            <button type="button" @click="stopScan()" x-show="scanning" class="btn-danger shrink-0">✕ Stop</button>
        </div>

        <template x-teleport="body">
            <div x-show="scanning" x-cloak wire:ignore
                class="fixed inset-0 bg-black/90 z-50 flex flex-col items-center justify-center p-4">
                <div class="w-full max-w-sm rounded-2xl overflow-hidden bg-black">
                    <video x-ref="stockScanVideo" class="w-full aspect-[4/3] object-cover" playsinline autoplay
                        muted></video>
                </div>
                <p class="text-white text-sm mt-4">Point the camera at a barcode or IMEI</p>
                <button @click="stopScan()" class="mt-4 btn-danger btn-sm">✕ Stop Scanning</button>
            </div>
        </template>

        {{-- Manual search fallback --}}
        <div class="relative pt-1" x-data="{ open: false }" @click.outside="open = false">
            <input wire:model.live.debounce.300ms="productSearch" wire:input="searchProduct" @focus="open = true"
                type="text" placeholder="…or search by name if you don't have a barcode" class="input text-sm"
                autocomplete="off" @disabled($branchId < 1)>

            @if (!empty($searchResults))
                <div class="absolute z-30 mt-1 w-full bg-white border border-gray-200
            rounded-xl shadow-lg max-h-80 overflow-y-auto"
                    x-show="open">
                    @foreach ($searchResults as $r)
                        <button type="button"
                            wire:click="selectFromSearch({{ $r['variant_id'] }}, '{{ addslashes($r['label']) }}', '{{ $r['tracking_type'] }}')"
                            @click="open = false"
                            class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 flex items-center justify-between border-b border-gray-50">
                            <div>
                                <span class="font-medium text-gray-900">{{ $r['label'] }}</span>
                                <span class="text-xs text-gray-400 ml-2">{{ $r['sku'] }}</span>
                            </div>
                            @if ($r['tracking_type'] === 'serialized')
                                <span class="badge badge-blue shrink-0 text-xs">IMEI</span>
                            @else
                                <span class="badge badge-gray shrink-0 text-xs">Qty</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Active IMEI-capture banner --}}
    @if ($activeSerializedVariantId)
        @php $activeGroup = collect($batchItems)->firstWhere('variant_id', $activeSerializedVariantId); @endphp
        @if ($activeGroup)
            <div class="card p-4 bg-blue-50 border-blue-200 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-blue-900">📡 Scanning IMEIs for:
                        {{ $activeGroup['product_name'] }}</div>
                    <div class="text-xs text-blue-600 mt-0.5">
                        {{ count($activeGroup['serials']) }} IMEI(s) scanned so far. Keep scanning units, or click Done.
                    </div>
                </div>
                <button wire:click="finishSerializedGroup" class="btn-secondary btn-sm">Done</button>
            </div>
        @endif
    @endif

    {{-- Scan Queue --}}
    @if (!empty($batchItems))
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Scan Queue — {{ $this->batchTotals['lines'] }} product
                    line(s)</h3>
                <button wire:click="clearBatch"
                    wire:confirm="Clear the entire scan queue? Nothing already saved is affected."
                    class="text-xs text-red-500 hover:underline">Clear all</button>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach ($batchItems as $idx => $item)
                    <div class="p-4" wire:key="batch-{{ $item['key'] }}">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-900 text-sm">{{ $item['product_name'] }}</div>
                                <span
                                    class="badge {{ $item['tracking_type'] === 'serialized' ? 'badge-blue' : 'badge-gray' }} text-xs mt-1 inline-block">
                                    {{ $item['tracking_type'] === 'serialized' ? 'IMEI / Serialized' : 'Non-Serialized' }}
                                </span>
                            </div>

                            @if ($item['tracking_type'] === 'non_serialized')
                                <div class="flex items-center gap-2 shrink-0">
                                    <div>
                                        <label class="text-xs text-gray-400 block">Qty</label>
                                        <input wire:model.lazy="batchItems.{{ $idx }}.quantity" type="number"
                                            step="0.01" min="0.01"
                                            class="input w-24 text-sm text-right @error('batchItems.' . $idx . '.quantity') input-error @enderror">
                                        @error('batchItems.' . $idx . '.quantity')
                                            <p class="error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-400 block">Unit Cost (৳)</label>
                                        <input wire:model.lazy="batchItems.{{ $idx }}.unit_cost"
                                            type="number" step="0.01" min="0"
                                            class="input w-28 text-sm text-right @error('batchItems.' . $idx . '.unit_cost') input-error @enderror"
                                            placeholder="0.00">
                                        @error('batchItems.' . $idx . '.unit_cost')
                                            <p class="error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="text-right w-28">
                                        <label class="text-xs text-gray-400 block">Value</label>
                                        <div class="text-sm font-bold text-indigo-700 py-1.5">
                                            ৳{{ number_format((float) $item['quantity'] * (float) ($item['unit_cost'] ?: 0), 2) }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-gray-400">IMEIs</div>
                                    <div class="text-lg font-bold text-indigo-700">{{ count($item['serials']) }}</div>
                                </div>
                            @endif

                            <button wire:click="removeBatchItem('{{ $item['key'] }}')"
                                class="text-gray-300 hover:text-red-500 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        @if ($item['tracking_type'] === 'serialized' && !empty($item['serials']))
                            <div class="mt-3 space-y-1.5 pl-1">
                                <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-gray-400 px-1">
                                    <div class="col-span-4">IMEI</div>
                                    <div class="col-span-3">Cost (৳)</div>
                                    <div class="col-span-3">Warranty (mo)</div>
                                </div>
                                @foreach ($item['serials'] as $si => $serial)
                                    <div class="grid grid-cols-12 gap-2 items-center text-sm"
                                        wire:key="serial-{{ $item['key'] }}-{{ $si }}">
                                        <div class="col-span-4 font-mono text-gray-800">{{ $serial['serial_number'] }}
                                        </div>
                                        <div class="col-span-3">
                                            <input
                                                wire:model.lazy="batchItems.{{ $idx }}.serials.{{ $si }}.cost_price"
                                                type="number" step="0.01" min="0"
                                                class="input text-xs py-1" placeholder="0">
                                        </div>
                                        <div class="col-span-3">
                                            <input
                                                wire:model.lazy="batchItems.{{ $idx }}.serials.{{ $si }}.warranty_months"
                                                type="number" min="0" class="input text-xs py-1"
                                                placeholder="12">
                                        </div>
                                        <div class="col-span-1 text-center">
                                            <button
                                                wire:click="removeSerial('{{ $item['key'] }}', {{ $si }})"
                                                class="text-red-400 hover:text-red-600 text-xs">✕</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div
                class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    <span class="font-semibold">{{ $this->batchTotals['units'] }}</span> unit(s) ·
                    Total value: <span
                        class="font-bold text-indigo-700">৳{{ number_format($this->batchTotals['value'], 2) }}</span>
                </div>
                <button wire:click="saveBatch" wire:loading.attr="disabled" wire:target="saveBatch"
                    class="btn-primary">
                    <span wire:loading.remove wire:target="saveBatch">✓ Save {{ $this->batchTotals['lines'] }} Line(s)
                        as Opening Stock</span>
                    <span wire:loading wire:target="saveBatch">Saving…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Recent Entries --}}
    @if (!empty($recentEntries))
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Opening Stock Entries</h3>
                <a href="{{ route('inventory.adjustments') }}?type=opening_stock" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">View all →</a>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Product</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Qty</th>
                        <th class="table-th text-right">Total Cost</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">By</th>
                        <th class="table-th text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentEntries as $entry)
                        @php $isReversal = $entry['adjustment_type'] === 'opening_stock_reversal'; @endphp
                        <tr class="hover:bg-gray-50 {{ $isReversal ? 'bg-gray-50' : '' }}">
                            <td class="table-td text-sm text-gray-900">
                                {{ $entry['variant']['product']['name'] ?? '—' }}
                                <div class="text-xs text-gray-400">{{ $entry['variant']['sku'] ?? '' }}</div>
                                @if ($isReversal)
                                    <span class="badge badge-gray text-xs mt-1 inline-block">↩ Correction</span>
                                @endif
                            </td>
                            <td class="table-td text-sm text-gray-500">{{ $entry['branch']['name'] ?? '—' }}</td>
                            <td class="table-td text-right font-semibold {{ $isReversal ? 'text-red-600' : '' }}">
                                {{ $isReversal ? '−' : '' }}{{ $entry['quantity'] }}
                            </td>
                            <td class="table-td text-right text-indigo-700 font-semibold">
                                {{ $entry['total_cost'] > 0 ? '৳' . number_format($entry['total_cost'], 2) : '—' }}
                            </td>
                            <td class="table-td text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($entry['created_at'])->format('d M Y H:i') }}
                            </td>
                            <td class="table-td text-xs text-gray-400">{{ $entry['created_by']['name'] ?? '—' }}</td>
                            <td class="table-td text-right">
                                @if ($isReversal)
                                    <span class="text-xs text-gray-400">—</span>
                                @elseif ($entry['reversed_at'])
                                    <span class="badge badge-gray text-xs">Reversed</span>
                                @else
                                    <button wire:click="openReverseModal({{ $entry['id'] }})"
                                        class="text-xs text-red-500 hover:underline font-medium">
                                        Reverse
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Reverse Confirmation Modal --}}
    @if ($showReverseModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
                <div>
                    <h3 class="font-bold text-gray-900 text-lg">Reverse Opening Stock Entry</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        This will roll back the stock quantity and post a correcting accounting entry
                        (Dr Opening Balance Equity / Cr Inventory). The original entry is kept for audit —
                        nothing is deleted.
                    </p>
                </div>
                <div>
                    <label class="label">Reason for correction *</label>
                    <textarea wire:model="reversalReason" rows="3" class="input @error('reversalReason') input-error @enderror"
                        placeholder="e.g. Wrong cost price entered — should be ৳320, not ৳230"></textarea>
                    @error('reversalReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3 justify-end pt-2 border-t border-gray-100">
                    <button wire:click="$set('showReverseModal', false)" class="btn-secondary">Cancel</button>
                    <button wire:click="confirmReverse" wire:loading.attr="disabled" wire:target="confirmReverse"
                        class="btn-danger">
                        <span wire:loading.remove wire:target="confirmReverse">Confirm Reversal</span>
                        <span wire:loading wire:target="confirmReverse">Reversing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
