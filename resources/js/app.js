import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm.js';

Alpine.store('desktop', {
    sidebarOpen: false,
    toggleSidebar() {
        this.sidebarOpen = ! this.sidebarOpen;
    },
});

Alpine.data('barcodeScanner', () => ({
    detector: null,
    frameRequest: null,
    open: false,
    scannerError: '',
    scannerRunning: false,
    scannerStatus: '',
    stream: null,
    zxingBarcodeFormat: null,
    zxingControls: null,
    zxingReader: null,

    focusBarcodeInput() {
        this.scannerStatus = 'Ready for USB scanner input.';

        this.$nextTick(() => {
            this.$refs.barcodeInput?.focus();
            this.$refs.barcodeInput?.select();
        });
    },

    markKeyboardScan() {
        this.scannerStatus = 'Barcode captured from scanner input.';
        this.$refs.barcodeInput?.blur();
    },

    async startCamera($wire) {
        this.stop(false);
        this.open = true;
        this.scannerError = '';
        this.scannerStatus = 'Starting camera...';

        if (! navigator.mediaDevices?.getUserMedia) {
            this.scannerError = 'Camera access is not available on this device.';
            this.scannerStatus = '';

            return;
        }

        try {
            await this.$nextTick();

            if (! this.$refs.video) {
                throw new Error('Scanner video surface is not ready.');
            }

            if (globalThis.BarcodeDetector) {
                try {
                    await this.startNativeScanner($wire);

                    return;
                } catch {
                    this.stop(false);
                    this.open = true;
                    this.scannerError = '';
                    this.scannerStatus = 'Native scanner unavailable. Starting bundled decoder...';
                    await this.$nextTick();
                }
            }

            await this.startZxingScanner($wire);
        } catch (error) {
            this.scannerError = error?.message || 'Unable to start barcode scanner.';
            this.scannerStatus = '';
            this.stop(false);
        }
    },

    async startNativeScanner($wire) {
        const requestedFormats = [
            'ean_13',
            'ean_8',
            'upc_a',
            'upc_e',
            'code_128',
            'code_39',
            'code_93',
            'itf',
            'qr_code',
            'data_matrix',
            'pdf417',
        ];
        const supportedFormats = await globalThis.BarcodeDetector.getSupportedFormats?.();
        const formats = Array.isArray(supportedFormats) && supportedFormats.length > 0
            ? requestedFormats.filter((format) => supportedFormats.includes(format))
            : requestedFormats;

        this.detector = new globalThis.BarcodeDetector(formats.length > 0 ? { formats } : undefined);
        this.stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: 'environment' },
                height: { ideal: 720 },
                width: { ideal: 1280 },
            },
        });

        this.$refs.video.srcObject = this.stream;
        await this.$refs.video.play();

        this.scannerRunning = true;
        this.scannerStatus = 'Scanning with native camera decoder...';
        this.scanNativeFrame($wire);
    },

    async startZxingScanner($wire) {
        const { BarcodeFormat, BrowserMultiFormatReader } = await import('@zxing/browser');

        this.zxingBarcodeFormat = BarcodeFormat;
        this.zxingReader = new BrowserMultiFormatReader(undefined, {
            delayBetweenScanAttempts: 150,
            delayBetweenScanSuccess: 500,
            tryPlayVideoTimeout: 5000,
        });
        this.scannerRunning = true;
        this.scannerStatus = 'Scanning with bundled camera decoder...';

        this.zxingControls = await this.zxingReader.decodeFromConstraints(
            {
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    height: { ideal: 720 },
                    width: { ideal: 1280 },
                },
            },
            this.$refs.video,
            async (result, error, controls) => {
                if (! this.scannerRunning || ! result) {
                    return;
                }

                const rawValue = result.getText?.()?.trim();

                if (! rawValue) {
                    return;
                }

                this.scannerRunning = false;
                controls.stop();
                this.zxingControls = null;

                await $wire.applyScannedBarcode(rawValue, this.formatZxingBarcodeType(result.getBarcodeFormat?.()));
                this.scannerStatus = `Barcode captured: ${rawValue}`;
                this.stop();
            },
        );
    },

    async scanNativeFrame($wire) {
        if (! this.scannerRunning || ! this.detector || ! this.$refs.video) {
            return;
        }

        try {
            const barcodes = await this.detector.detect(this.$refs.video);
            const barcode = barcodes[0];
            const rawValue = barcode?.rawValue?.trim();

            if (rawValue) {
                await $wire.applyScannedBarcode(rawValue, barcode.format || null);
                this.scannerStatus = `Barcode captured: ${rawValue}`;
                this.stop();

                return;
            }
        } catch (error) {
            this.scannerError = error?.message || 'Unable to read barcode.';
            this.stop(false);

            return;
        }

        this.frameRequest = requestAnimationFrame(() => this.scanNativeFrame($wire));
    },

    formatZxingBarcodeType(format) {
        if (format === undefined || format === null) {
            return null;
        }

        return (this.zxingBarcodeFormat?.[format] || String(format)).replaceAll('_', '-');
    },

    stop(close = true) {
        this.scannerRunning = false;

        if (this.zxingControls) {
            this.zxingControls.stop();
            this.zxingControls = null;
        }

        if (this.frameRequest) {
            cancelAnimationFrame(this.frameRequest);
            this.frameRequest = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }

        if (close) {
            this.open = false;
        }
    },
}));

Livewire.start();
