# Printing and Hardware

## Hardware Targets

- 58 mm and 80 mm thermal printers
- A4 printers
- USB barcode scanners
- Barcode and QR label printers
- Cash drawers
- Customer display

## Current Position

Phase 3 records printer names during first-run setup. Phase 5 product creation supports barcode capture through manual entry, USB keyboard-wedge scanners, and camera scanning through a native browser/Electron decoder or bundled ZXing fallback.

Actual printing, device discovery, cash drawer control, POS scanner workflows, and scale integration remain future modules.

## Printing Strategy

- Keep print templates configurable.
- Support thermal receipt and A4 invoice layouts.
- Store print history for invoices and reprints.
- Mark duplicate invoices clearly.
- Avoid CDN fonts or external print assets.

## Barcode Strategy

Most USB barcode scanners work as keyboard-wedge devices. The product form already exposes a USB scan focus action for barcode capture. The POS screen should later support fast keyboard focus and scan entry before direct hardware APIs are needed.

## NativePHP Role

NativePHP will provide the packaged desktop shell and can later bridge to OS-level printing, file dialogs, and device integration where browser-only APIs are not enough.
