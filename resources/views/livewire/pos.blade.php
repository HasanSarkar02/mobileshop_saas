@php
    $providerLabels = [
        'cash' => 'Cash',
        'bank' => 'Bank',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'upay' => 'Upay',
        'card' => 'Card',
        'other' => 'Other',
        'finance_partner' => 'EMI / Finance Partner',
        'customer_credit' => 'Credit / Baki',
    ];
    $providerOptions = array_merge(
        $this->paymentAccounts
            ->groupBy('provider')
            ->map(fn($g, $prov) => ['type' => $prov, 'label' => $providerLabels[$prov] ?? ucfirst($prov)])
            ->values()
            ->toArray(),
        [['type' => 'finance_partner', 'label' => 'EMI / Finance Partner']],
        [['type' => 'customer_credit', 'label' => 'Credit / Baki']],
    );
@endphp

<div class="h-screen flex flex-col select-none" x-data="{
    activeTab: 'cart',
    showProductResults: false,
    showCustomerResults: false,
}"
    @keydown.f2.window.prevent="$refs.productSearch?.focus(); showProductResults = true"
    @keydown.f8.window.prevent="$wire.showDiscountPanel = !$wire.showDiscountPanel"
    @keydown.f12.window.prevent="if (@js(count($cart)) > 0) $wire.confirmSale()"
    @keydown.escape.window="showProductResults = false; showCustomerResults = false; $wire.showUnitPicker = false">

    <div wire:ignore x-data="{
        toasts: [],
        add(detail) {
            const data = typeof detail === 'string' ? { message: detail, type: 'success' } : detail;
            const id = Date.now() + Math.random();
    
            this.toasts.push({
                id,
                type: data.type || 'success',
                message: data.message || ''
            });
            setTimeout(() => {
                this.remove(id);
            }, 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }" x-on:notify.window="add($event.detail)"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">

        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                :class="{
                    'bg-emerald-600': toast.type === 'success',
                    'bg-red-600': toast.type === 'error',
                    'bg-amber-600': toast.type === 'warning'
                }"
                class="pointer-events-auto text-white px-4 py-3 rounded-xl shadow-xl text-sm font-medium min-w-[250px] flex items-center justify-between gap-3">

                <span x-text="toast.message"></span>

                <button @click="remove(toast.id)" class="text-white/80 hover:text-white font-bold ml-2">
                    ✕
                </button>
            </div>
        </template>
    </div>
    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <header class="bg-white border-b border-gray-200 px-4 py-2 flex items-center gap-3 shrink-0 z-20">
        <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-500 hover:text-gray-700 p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <span class="font-bold text-indigo-700">POS</span>
        <span class="text-xs text-gray-400 hidden sm:block">{{ auth()->user()->shop?->name }}</span>
        <span class="hidden sm:block text-xs text-gray-300">|</span>
        <span class="text-xs text-gray-500 hidden sm:block">
            {{ \App\Models\Branch::find($currentBranchId)?->name ?? 'Main Branch' }}
        </span>

        {{-- Hold Sale Button --}}
        @if (!empty($cart))
            <button wire:click="holdSale" title="Hold this sale — save cart and start a new one"
                class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors">
                ⏸ Hold Sale
            </button>
        @endif

        {{-- Held Sales indicator --}}
        @if (count($this->heldSales) > 0)
            <button wire:click="$toggle('showHeldSales')"
                class="relative flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors">
                📋 Held ({{ count($this->heldSales) }})
            </button>
        @endif

        <div class="ml-auto flex items-center gap-2">
            <span class="text-xs text-gray-400 hidden lg:block">F2=Search F8=Discount F12=Confirm</span>
            <button @click="toggleFullscreen()" title="Toggle Fullscreen"
                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x-show="!isFullscreen"
                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
            </button>
        </div>

    </header>

    {{-- ── SALE COMPLETE OVERLAY ──────────────────────────────────────────── --}}
    @if ($completedSaleId)
        <div class="absolute inset-0 bg-gray-900/80 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center space-y-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Sale Complete!</h2>
                <p class="text-gray-500 text-sm">Grand Total: <strong
                        class="text-gray-900">৳{{ number_format($this->totals['grandTotal'], 2) }}</strong></p>
                <div class="flex flex-col gap-2 pt-2">
                    <a href="{{ route('documents.sale', $completedSaleId) }}" target="_blank"
                        class="btn-primary w-full">
                        🖨 Print Receipt
                    </a>
                    <button wire:click="startNewSale" class="btn-secondary w-full">
                        + New Sale
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── MAIN SPLIT LAYOUT ──────────────────────────────────────────────── --}}
    <div class="flex-1 flex overflow-hidden pb-13 lg:pb-0">

        {{-- LEFT — Cart (60%) --}}
        <div class="flex-1 flex flex-col overflow-hidden" :class="{ 'hidden lg:flex': activeTab !== 'cart' }">

            {{-- Barcode / Product Search --}}
            <div class="bg-white border-b border-gray-200 p-3 space-y-2">
                {{-- Barcode/IMEI scan input --}}
                <div class="relative" x-data="{
                    scanning: false,
                    videoStream: null,
                    detector: null,
                    lastCode: null,
                    lastScanAt: 0,
                
                    async startScan() {
                        if (this.scanning) return; // already running — never request a second stream
                        if (!('BarcodeDetector' in window)) {
                            $dispatch('notify', { type: 'error', message: 'Camera scanning isn\'t supported in this browser. Try Chrome on Android, or use a USB barcode scanner.' });
                            return;
                        }
                        if (!window.isSecureContext) {
                            $dispatch('notify', { type: 'error', message: 'Camera access needs HTTPS. Ask your admin to enable SSL for this site.' });
                            return;
                        }
                        this.detector = new BarcodeDetector({ formats: ['code_128', 'ean_13', 'ean_8', 'qr_code'] });
                        try {
                            this.videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                            this.$refs.posScanVideo.srcObject = this.videoStream;
                            await this.$refs.posScanVideo.play();
                            this.scanning = true;
                            this.detectLoop();
                        } catch (e) {
                            const msg = e.name === 'NotAllowedError' ?
                                'Camera permission denied. Please allow camera access in your browser settings and try again.' :
                                (e.name === 'NotFoundError' ? 'No camera found on this device.' : 'Could not start camera: ' + e.message);
                            $dispatch('notify', { type: 'error', message: msg });
                        }
                    },
                
                    async detectLoop() {
                        if (!this.scanning) return;
                        try {
                            const found = await this.detector.detect(this.$refs.posScanVideo);
                            if (found.length > 0) {
                                const val = found[0].rawValue;
                                const now = Date.now();
                                if (val !== this.lastCode || (now - this.lastScanAt) > 1500) {
                                    this.lastCode = val;
                                    this.lastScanAt = now;
                                    $wire.barcodeInput = val;
                                    $wire.processBarcode();
                                }
                            }
                        } catch (e) {}
                        if (this.scanning) requestAnimationFrame(() => this.detectLoop());
                    },
                
                    stopScan() {
                        this.scanning = false;
                        this.videoStream?.getTracks().forEach(t => t.stop());
                        this.videoStream = null;
                    }
                }">
                    <input type="text" wire:model="barcodeInput" wire:keydown.enter="processBarcode"
                        enterkeyhint="go" placeholder="Scan barcode / IMEI — press Enter"
                        class="w-full pl-10 pr-24 py-2.5 text-sm bg-indigo-50 border border-indigo-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white placeholder-indigo-300">
                    <svg class="w-5 h-5 text-indigo-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>

                    <div class="absolute right-2 top-1.5 flex items-center gap-1">
                        <button type="button" wire:click="processBarcode" wire:loading.attr="disabled"
                            wire:target="processBarcode" title="Add to cart"
                            class="p-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </button>

                        <button type="button" @click="startScan()" title="Scan with camera"
                            class="p-1.5 rounded-lg text-indigo-500 hover:bg-indigo-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        {{-- Camera viewfinder overlay — teleported out of the Livewire-managed
                             tree so it can never be torn down by a component re-render.
                             wire:ignore is kept as a defense-in-depth belt-and-braces measure. --}}
                        <template x-teleport="body">
                            <div x-show="scanning" x-cloak wire:ignore wire:key="pos-scan-viewfinder"
                                class="fixed inset-0 bg-black/90 z-50 flex flex-col items-center justify-center p-4"
                                style="display:none">
                                <div class="w-full max-w-sm rounded-2xl overflow-hidden bg-black">
                                    <video x-ref="posScanVideo" class="w-full aspect-[4/3] object-cover" playsinline
                                        autoplay muted></video>
                                </div>
                                <p class="text-white text-sm mt-4">Point the camera at a barcode or IMEI</p>
                                <button @click="stopScan()" class="mt-4 btn-danger btn-sm">✕ Stop Scanning</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Product name/sku search --}}
                <div class="relative">
                    <input x-ref="productSearch" type="search" wire:model.live.debounce.300ms="productSearch"
                        placeholder="🔍 Search product by name or SKU..." class="input pl-4" autocomplete="off">

                    {{-- Dropdown — Livewire controlled (wire:show), NOT Alpine x-show --}}
                    <div wire:show="showProductDropdown"
                        class="absolute top-full left-0 right-0 z-30 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                        @foreach ($productResults as $result)
                            <button type="button"
                                wire:click="selectVariantFromSearch({{ $result['id'] }}, '{{ $result['tracking_type'] }}')"
                                class="w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-indigo-50 border-b border-gray-50 last:border-0 text-left">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900">{{ $result['label'] }}</div>
                                    <div class="text-xs text-gray-400">{{ $result['sku'] }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-indigo-700">
                                        ৳{{ number_format($result['selling_price'], 2) }}</div>
                                    @if ($result['tracking_type'] === 'serialized')
                                        <span class="badge badge-blue text-xs">IMEI</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                        <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-xs text-gray-400">{{ count($productResults) }} result(s)</span>
                            <button wire:click="closeProductSearch" class="text-xs text-gray-400 hover:text-gray-600">
                                Close ×
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto">
                @if (empty($cart))
                    <div class="flex flex-col items-center justify-center h-full text-gray-300 select-none">
                        <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <p class="text-sm font-medium">Cart is empty</p>
                        <p class="text-xs mt-1">Search or scan a product to begin</p>
                    </div>
                @else
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                            <tr>
                                <th class="table-th text-left">Product</th>
                                <th class="table-th text-right w-28">Price</th>
                                <th class="table-th text-center w-24">Qty</th>
                                <th class="table-th text-right w-28">Discount</th>
                                <th class="table-th text-right w-28">Total</th>
                                <th class="table-th w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($cart as $idx => $item)
                                <tr class="hover:bg-gray-50 {{ $item['is_below_cost'] ? 'bg-red-50' : '' }}"
                                    wire:key="cart-{{ $idx }}">
                                    <td class="table-td">
                                        <div class="font-semibold text-gray-900 text-sm leading-tight">
                                            {{ $item['product_name'] }}
                                        </div>
                                        @if ($item['variant_label'])
                                            <div class="text-xs text-gray-500">{{ $item['variant_label'] }}</div>
                                        @endif
                                        @if ($item['serial_number'])
                                            <div class="text-xs font-mono text-indigo-500">
                                                {{ $item['serial_number'] }}</div>
                                        @endif
                                        @if ($item['is_below_cost'])
                                            <div class="text-xs text-red-500 font-medium">⚠ Below cost price</div>
                                        @endif
                                    </td>
                                    <td class="table-td text-right">
                                        <input wire:model.lazy="cart.{{ $idx }}.unit_price" type="number"
                                            step="0.01" min="0"
                                            class="w-24 text-right text-sm border border-gray-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                                    </td>
                                    <td class="table-td">
                                        <div class="flex items-center justify-center gap-1">
                                            @if (empty($item['product_unit_id']))
                                                <button wire:click="updateQuantity({{ $idx }}, -1)"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm transition-colors">−</button>
                                                <span
                                                    class="w-8 text-center font-semibold text-sm">{{ $item['quantity'] }}</span>
                                                <button wire:click="updateQuantity({{ $idx }}, 1)"
                                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-sm transition-colors">+</button>
                                            @else
                                                <span class="text-sm text-gray-500 px-2">1</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="table-td text-right">
                                        <div class="flex items-center gap-1 justify-end">
                                            <select wire:model.live="cart.{{ $idx }}.discount_type"
                                                class="text-xs border border-gray-200 rounded px-1 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                                <option value="none">—</option>
                                                <option value="percentage">%</option>
                                                <option value="flat">৳</option>
                                            </select>
                                            @if ($item['discount_type'] !== 'none')
                                                <input wire:model.lazy="cart.{{ $idx }}.discount_value"
                                                    type="number" min="0" step="0.01"
                                                    class="w-16 text-right text-xs border border-gray-200 rounded px-1 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                                            @endif
                                        </div>
                                        @if ($item['discount_amount'] > 0)
                                            <div class="text-xs text-red-500 text-right mt-0.5">
                                                −৳{{ number_format($item['discount_amount'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="table-td text-right font-bold text-gray-900">
                                        ৳{{ number_format($item['line_total'], 2) }}
                                        @if ($vatEnabled && $item['vat_amount'] > 0)
                                            <div class="text-xs text-gray-400 font-normal">+VAT
                                                ৳{{ number_format($item['vat_amount'], 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="table-td text-center">
                                        <button wire:click="removeItem({{ $idx }})"
                                            class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Cart Footer / Order Totals --}}
            <div class="bg-white border-t border-gray-200 p-4 space-y-2 shrink-0">
                {{-- Order Discount Toggle --}}
                <div class="flex items-center justify-between">
                    <button wire:click="$toggle('showDiscountPanel')"
                        class="text-xs text-indigo-600 hover:underline font-medium flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Order Discount (F8)
                    </button>
                    @if ($this->totals['orderDiscount'] > 0)
                        <span
                            class="text-xs text-red-500 font-medium">−৳{{ number_format($this->totals['orderDiscount'], 2) }}</span>
                    @endif
                </div>

                @if ($showDiscountPanel)
                    <div class="flex items-center gap-2 p-3 bg-indigo-50 rounded-xl">
                        <select wire:model.live="orderDiscountType" class="input w-auto text-sm py-1">
                            <option value="none">No discount</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="flat">Flat Amount (৳)</option>
                        </select>
                        @if ($orderDiscountType !== 'none')
                            <input wire:model.live="orderDiscountValue" type="number" min="0" step="0.01"
                                class="input w-28 text-sm py-1"
                                placeholder="{{ $orderDiscountType === 'percentage' ? '10' : '500' }}">
                            <span
                                class="text-sm text-gray-500">{{ $orderDiscountType === 'percentage' ? '%' : '৳' }}</span>
                        @endif
                    </div>
                @endif

                {{-- Totals --}}
                @php $t = $this->totals; @endphp
                <div class="space-y-1 text-sm pt-1">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>৳{{ number_format($t['subtotal'], 2) }}</span>
                    </div>
                    @if ($t['totalDiscount'] > 0)
                        <div class="flex justify-between text-red-500">
                            <span>Total Discount</span>
                            <span>−৳{{ number_format($t['totalDiscount'], 2) }}</span>
                        </div>
                    @endif
                    @if ($vatEnabled && $t['vatTotal'] > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>VAT ({{ $vatRate }}%)</span>
                            <span>৳{{ number_format($t['vatTotal'], 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-bold text-base text-gray-900 pt-1 border-t border-gray-200">
                        <span>Grand Total</span>
                        <span class="text-indigo-700">৳{{ number_format($t['grandTotal'], 2) }}</span>
                    </div>
                    @if (count($cart) > 0)
                        <div
                            class="flex justify-between text-xs {{ $t['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            <span>Est. Profit</span>
                            <span>{{ $t['profit'] >= 0 ? '+' : '' }}৳{{ number_format($t['profit'], 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT — Customer + Payment (40%) --}}
        <div class="w-full lg:w-96 xl:w-[420px] flex flex-col bg-white border-l border-gray-200 overflow-hidden"
            :class="{ 'hidden lg:flex': activeTab !== 'payment' }">

            {{-- Customer Section --}}
            <div class="border-b border-gray-100 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</h3>
                    @if ($customerId)
                        <button wire:click="clearCustomer"
                            class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                            × Clear
                        </button>
                    @endif
                </div>

                @if ($customerId && !empty($customerDisplay))
                    {{-- Customer selected --}}
                    <div class="bg-indigo-50 rounded-xl p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-200 flex items-center justify-center shrink-0">
                                <span
                                    class="text-sm font-bold text-indigo-700">{{ strtoupper(substr($customerDisplay['name'], 0, 1)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-indigo-900 text-sm">{{ $customerDisplay['name'] }}
                                </div>
                                <div class="text-xs text-indigo-600">{{ $customerDisplay['phone'] }}</div>
                            </div>
                            @if ($customerDisplay['current_balance'] > 0)
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-red-600 font-bold">Due:
                                        ৳{{ number_format($customerDisplay['current_balance'], 2) }}</div>
                                    @if ($customerDisplay['credit_limit'] > 0)
                                        <div class="text-xs text-gray-400">Limit:
                                            ৳{{ number_format($customerDisplay['credit_limit'], 0) }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Previous due collection (optional) --}}
                        @if ($customerDisplay['current_balance'] > 0)
                            <div class="mt-3 pt-3 border-t border-indigo-200">
                                <label
                                    class="flex items-center gap-2 text-xs text-indigo-700 cursor-pointer font-medium mb-2">
                                    <input type="checkbox" wire:model.live="showDueCollection"
                                        class="rounded border-indigo-300 text-indigo-600">
                                    Collect existing due during this sale
                                </label>
                                @if ($showDueCollection)
                                    <div class="flex items-center gap-2">
                                        <input wire:model="dueCollectionAmount" type="number" min="0"
                                            max="{{ $customerDisplay['current_balance'] }}" step="0.01"
                                            class="input text-sm py-1.5 flex-1" placeholder="Amount to collect">
                                        <select wire:model="dueCollectionAccountId"
                                            class="input text-sm py-1.5 w-auto">
                                            <option value="0">Via…</option>
                                            @foreach ($this->paymentAccounts as $pa)
                                                <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="text-xs text-indigo-500 mt-1">
                                        Max: ৳{{ number_format($customerDisplay['current_balance'], 2) }}
                                        — this is separate from this sale's payment
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Customer search --}}
                    <div class="relative">
                        <input type="search" wire:model.live.debounce.300ms="customerSearch"
                            placeholder="Search by name or phone…" class="input text-sm" autocomplete="off">

                        {{-- Dropdown — Livewire controlled --}}
                        <div wire:show="showCustomerDropdown"
                            class="absolute top-full left-0 right-0 z-30 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            @foreach ($customerResults as $c)
                                <button type="button" wire:click="selectCustomer({{ $c['id'] }})"
                                    class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-indigo-50 border-b border-gray-50 last:border-0 text-left">
                                    <div>
                                        <div class="font-medium text-sm text-gray-900">{{ $c['name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $c['phone'] }}</div>
                                    </div>
                                    @if ($c['current_balance'] > 0)
                                        <span class="text-xs text-red-500 font-bold shrink-0 ml-2">
                                            Due: ৳{{ number_format($c['current_balance'], 2) }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                            <div class="px-4 py-1.5 border-t border-gray-100 text-right">
                                <button wire:click="closeCustomerSearch"
                                    class="text-xs text-gray-400 hover:text-gray-600">Close ×</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">No customer?</span>
                        {{-- FIX: $toggle instead of $set with ! operator --}}
                        <button wire:click="$toggle('showQuickCustomer')"
                            class="text-xs text-indigo-600 hover:underline font-medium">
                            + Quick add
                        </button>
                        <span class="text-xs text-gray-300">or</span>
                        <span class="text-xs text-gray-400">leave blank for Walk-in</span>
                    </div>

                    @if ($showQuickCustomer)
                        <div class="bg-gray-50 rounded-xl p-3 space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500 mb-0.5 block">Name *</label>
                                    <input wire:model="qcName" type="text" class="input text-sm py-1.5"
                                        placeholder="Customer name">
                                    @error('qcName')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500 mb-0.5 block">Phone *</label>
                                    <input wire:model="qcPhone" type="tel" class="input text-sm py-1.5"
                                        placeholder="01XXXXXXXXX">
                                    @error('qcPhone')
                                        <p class="error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="quickAddCustomer" class="btn-success btn-sm flex-1">Save &
                                    Select</button>
                                <button wire:click="$set('showQuickCustomer', false)"
                                    class="btn-secondary btn-sm">Cancel</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Payment Lines --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment</h3>
                    <button wire:click="addPaymentLine" class="text-xs text-indigo-600 hover:underline font-medium">
                        + Add Method
                    </button>
                </div>

                @foreach ($paymentLines as $idx => $line)
                    <div class="border border-gray-200 rounded-xl p-3 space-y-2" wire:key="pay-{{ $idx }}">
                        <div class="flex items-center gap-2">
                            {{-- Payment type selector --}}
                            <select wire:model.live="paymentLines.{{ $idx }}.type"
                                class="input text-sm py-1.5 flex-1">
                                @foreach ($providerOptions as $opt)
                                    <option value="{{ $opt['type'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>

                            @if (count($paymentLines) > 1)
                                <button wire:click="removePaymentLine({{ $idx }})"
                                    class="text-gray-300 hover:text-red-500 transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>

                        {{-- Account selector (non-baki, non-EMI) --}}
                        @if (!in_array($line['type'], ['customer_credit', 'finance_partner']))
                            <select wire:model="paymentLines.{{ $idx }}.payment_account_id"
                                class="input text-sm py-1.5 w-full">
                                <option value="">Select account…</option>
                                @foreach ($this->paymentAccounts->where('provider', $line['type']) as $pa)
                                    <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Finance partner selector --}}
                        @if ($line['type'] === 'finance_partner')
                            <select wire:model="paymentLines.{{ $idx }}.finance_partner_id"
                                class="input text-sm py-1.5 w-full">
                                <option value="">Select partner…</option>
                                @foreach ($this->financePartners as $fp)
                                    <option value="{{ $fp->id }}">{{ $fp->name }}</option>
                                @endforeach
                            </select>
                            @if ($line['type'] === 'finance_partner')
                                <p class="text-xs text-gray-400">This amount will be recorded as receivable from
                                    the
                                    EMI company.</p>
                            @endif
                        @endif

                        {{-- Reference number --}}
                        @if (in_array($line['type'], ['bkash', 'nagad', 'rocket', 'upay', 'bank', 'card']))
                            <input wire:model="paymentLines.{{ $idx }}.reference" type="text"
                                class="input text-sm py-1.5" placeholder="Transaction / reference no.">
                        @endif

                        {{-- Amount --}}
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 font-medium shrink-0">৳</span>
                            <input wire:model.live="paymentLines.{{ $idx }}.amount" type="number"
                                step="0.01" min="0" class="input text-sm py-1.5 flex-1 font-semibold"
                                placeholder="0.00">
                            <button wire:click="fillRemaining({{ $idx }})" title="Fill remaining amount"
                                class="text-xs text-indigo-600 hover:underline font-medium shrink-0 whitespace-nowrap">
                                Fill ৳{{ number_format($this->totals['remaining'], 2) }}
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- Notes --}}
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Sale Notes (optional)</label>
                    <textarea wire:model="saleNotes" rows="2" class="input text-sm" placeholder="Internal notes…"></textarea>
                </div>
            </div>

            {{-- Payment Summary + Confirm --}}
            <div class="border-t border-gray-200 p-4 space-y-3 shrink-0">
                @php $t = $this->totals; @endphp

                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between font-bold text-base">
                        <span>Total</span>
                        <span class="text-indigo-700">৳{{ number_format($t['grandTotal'], 2) }}</span>
                    </div>
                    <div class="flex justify-between {{ $t['salePaid'] > 0 ? 'text-gray-700' : 'text-gray-400' }}">
                        <span>Paid</span>
                        <span>৳{{ number_format($t['salePaid'], 2) }}</span>
                    </div>
                    @if ($dueCollectionAmount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Due Collection</span>
                            <span>+৳{{ number_format($dueCollectionAmount, 2) }}</span>
                        </div>
                    @endif
                    @if ($t['change'] > 0)
                        <div
                            class="flex justify-between text-green-700 font-semibold bg-green-50 rounded-lg px-3 py-1.5">
                            <span>Change Due</span>
                            <span>৳{{ number_format($t['change'], 2) }}</span>
                        </div>
                    @endif
                    @if ($t['remaining'] > 0.01)
                        <div class="flex justify-between text-red-600 font-medium">
                            <span>Still Needed</span>
                            <span>৳{{ number_format($t['remaining'], 2) }}</span>
                        </div>
                    @endif
                </div>

                <button wire:click="confirmSale" wire:loading.attr="disabled" wire:target="confirmSale"
                    @disabled(count($cart) === 0 || $t['remaining'] > 0.01)
                    class="w-full py-3.5 rounded-xl font-bold text-sm transition-all
                        {{ count($cart) > 0 && $t['remaining'] <= 0.01
                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md hover:shadow-lg active:scale-[0.98]'
                            : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                    <span wire:loading.remove wire:target="confirmSale">
                        ✓ Confirm Sale (F12)
                    </span>
                    <span wire:loading wire:target="confirmSale" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                class="opacity-25" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                        </svg>
                        Processing…
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MOBILE TAB BAR ──────────────────────────────────────────────────── --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 flex z-40">
        @foreach ([['key' => 'cart', 'label' => 'Cart (' . count($cart) . ')', 'd' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'], ['key' => 'payment', 'label' => 'Payment', 'd' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z']] as $tab)
            <button @click="activeTab = '{{ $tab['key'] }}'"
                class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium transition-colors"
                :class="activeTab === '{{ $tab['key'] }}' ? 'text-indigo-600' : 'text-gray-400'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['d'] }}" />
                </svg>
                {{ $tab['label'] }}
            </button>
        @endforeach
    </nav>

    {{-- ── UNIT PICKER MODAL ───────────────────────────────────────────────── --}}
    @if ($showUnitPicker)
        <div class="absolute inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[70vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Select IMEI Unit</h3>
                    <button wire:click="$set('showUnitPicker', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-3 border-b border-gray-100">
                    <input type="search" wire:model.live="unitSearchQuery" placeholder="Search IMEI…"
                        class="input text-sm">
                </div>
                <div class="overflow-y-auto divide-y divide-gray-50">
                    @foreach (collect($availableUnits)->filter(fn($u) => !$unitSearchQuery || str_contains($u['serial_number'], $unitSearchQuery)) as $unit)
                        <button type="button" wire:click="selectUnitFromPicker({{ $unit['id'] }})"
                            class="w-full flex items-center justify-between px-5 py-3.5 hover:bg-indigo-50 text-left transition-colors">
                            <div>
                                <div class="font-mono font-semibold text-gray-900">{{ $unit['serial_number'] }}
                                </div>
                                @if ($unit['secondary'])
                                    <div class="font-mono text-xs text-gray-400">{{ $unit['secondary'] }}</div>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400">
                                Cost: ৳{{ number_format($unit['cost_price'], 2) }}
                            </div>
                        </button>
                    @endforeach
                    @if (empty($availableUnits))
                        <div class="px-5 py-8 text-center text-gray-400 text-sm">No units available</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ── HELD SALES MODAL ───────────────────────────────────────────────── --}}
    @if ($showHeldSales)
        <div class="absolute inset-0 bg-black/50 z-40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Held Sales ({{ count($this->heldSales) }})</h3>
                    <button wire:click="$set('showHeldSales', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="overflow-y-auto divide-y divide-gray-100">
                    @forelse($this->heldSales as $held)
                        <div class="px-5 py-4 flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-semibold text-gray-900 text-sm">{{ $held['customer_name'] }}</span>
                                    <span class="badge badge-yellow text-xs">{{ $held['held_at'] }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $held['item_count'] }} item(s) · Total:
                                    ৳{{ number_format($held['total'], 2) }}
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button wire:click="resumeHeldSale('{{ $held['id'] }}')"
                                    class="btn-success btn-sm">Resume</button>
                                <button wire:click="discardHeldSale('{{ $held['id'] }}')"
                                    wire:confirm="Discard this held sale?" class="btn-danger btn-sm">Discard</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-gray-400 text-sm">No held sales.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
