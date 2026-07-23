{{--
    Usage:
    <x-barcode-scanner event="barcode-scanned" label="Scan IMEI / Barcode" :continuous="true" />

    Emits window event: { detail: { value: '...' } }
    Listen in Livewire: #[On('barcode-scanned')]
--}}

@props([
    'event' => 'barcode-scanned',
    'label' => 'Scan Barcode',
    'continuous' => false,
    'inputId' => 'barcode-input-' . uniqid(),
    'showManualInput' => true,
])

<div x-data="{
    scanning: false,
    cameraAvailable: false,
    videoStream: null,
    detector: null,
    scanLoop: null,
    continuous: {{ $continuous ? 'true' : 'false' }},
    lastScanned: null,

    async init() {
        // Check BarcodeDetector support
        if ('BarcodeDetector' in window) {
            try {
                this.detector = new BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'qr_code', 'code_128', 'code_39', 'upc_a', 'upc_e', 'itf', 'data_matrix']
                });
                this.cameraAvailable = true;
            } catch (e) {
                this.cameraAvailable = false;
            }
        }
    },

    async startCamera() {
        if (!this.detector) return;
        try {
            this.videoStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: 640, height: 480 }
            });
            this.$refs.video.srcObject = this.videoStream;
            await this.$refs.video.play();
            this.scanning = true;
            this.detect();
        } catch (e) {
            alert('Camera access denied. Use manual input below.');
        }
    },

    async detect() {
        if (!this.scanning || !this.detector) return;
        try {
            const barcodes = await this.detector.detect(this.$refs.video);
            if (barcodes.length > 0) {
                const value = barcodes[0].rawValue;
                if (value && value !== this.lastScanned) {
                    this.lastScanned = value;
                    this.onScanned(value);
                    if (!this.continuous) {
                        this.stopCamera();
                        return;
                    }
                    // Continuous: brief delay then continue
                    setTimeout(() => {
                        this.lastScanned = null;
                        this.scanLoop = requestAnimationFrame(() => this.detect());
                    }, 1500);
                    return;
                }
            }
        } catch (e) {}
        this.scanLoop = requestAnimationFrame(() => this.detect());
    },

    stopCamera() {
        this.scanning = false;
        if (this.videoStream) {
            this.videoStream.getTracks().forEach(t => t.stop());
            this.videoStream = null;
        }
        if (this.scanLoop) {
            cancelAnimationFrame(this.scanLoop);
        }
    },

    onScanned(value) {
        // Dispatch browser event — Livewire #[On] will catch it
        window.dispatchEvent(new CustomEvent('{{ $event }}', { detail: { value } }));
        // Also set the manual input for visual feedback
        const input = document.getElementById('{{ $inputId }}');
        if (input) {
            input.value = value;
            input.dispatchEvent(new Event('input'));
        }
    }
}" @keydown.escape.window="stopCamera()" class="space-y-2">
    {{-- Manual input (always available) --}}
    <div class="flex items-center gap-2">
        @if ($showManualInput)
            <input id="{{ $inputId }}" type="text" placeholder="{{ $label }}" autocomplete="off"
                class="input flex-1 font-mono"
                @keydown.enter.prevent="onScanned($event.target.value); $event.target.value = ''">
        @endif

        <button type="button" x-show="cameraAvailable && !scanning" @click="startCamera()"
            class="btn-secondary btn-sm shrink-0 flex items-center gap-1.5" title="Scan with camera">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Camera
        </button>

        <button type="button" x-show="scanning" @click="stopCamera()" class="btn-danger btn-sm shrink-0">
            ✕ Stop
        </button>
    </div>

    {{-- Camera viewfinder --}}
    <div x-show="scanning" class="relative rounded-xl overflow-hidden bg-black" style="display:none;">
        <video x-ref="video" class="w-full max-h-56 object-cover" playsinline muted></video>

        {{-- Scan line animation --}}
        <div class="absolute inset-x-0 top-0 pointer-events-none">
            <div class="mx-auto w-3/4 h-0.5 bg-green-400 opacity-80 animate-bounce mt-4"></div>
        </div>

        {{-- Corner guides --}}
        <div class="absolute inset-4 pointer-events-none">
            <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-green-400 rounded-tl-lg"></div>
            <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-green-400 rounded-tr-lg"></div>
            <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-green-400 rounded-bl-lg"></div>
            <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-green-400 rounded-br-lg"></div>
        </div>

        <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-xs text-center py-2">
            {{ $continuous ? 'Continuous scan mode — point at barcode' : 'Point camera at barcode' }}
        </div>
    </div>

    {{-- Scanned value feedback --}}
    <div x-show="lastScanned" x-text="'✓ Scanned: ' + lastScanned"
        class="text-xs text-green-600 font-mono font-semibold" style="display:none;"></div>
</div>
