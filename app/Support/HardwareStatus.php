<?php

namespace App\Support;

use App\Models\ApplicationSetting;
use RuntimeException;

class HardwareStatus
{
    public function summary(): array
    {
        $printers = $this->availablePrinters();
        $configured = (string) ApplicationSetting::getValue('printing.receipt_printer_name', '');

        return [
            'platform' => PHP_OS_FAMILY,
            'browser_printing' => true,
            'camera_barcode' => false,
            'configured_printer' => $configured,
            'configured_detected' => $configured !== '' && in_array($configured, $printers, true),
            'printers' => $printers,
        ];
    }

    public function printTest(string $printerName): void
    {
        abort_unless(PHP_OS_FAMILY === 'Windows', 422, 'Direct printer testing is available on Windows desktop deployments.');
        abort_if(trim($printerName) === '', 422, 'Configure a receipt printer before sending a test page.');

        $script = '$lines = @("MedStore printer test", "Store: '.addslashes((string) config('pharmacy.store_code')).'", "Time: '.addslashes(now()->format('Y-m-d H:i:s')).'"); $lines | Out-Printer -Name '.$this->powershellQuote($printerName);
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $command = 'powershell.exe -NoProfile -NonInteractive -EncodedCommand '.$encoded;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('Windows could not send the printer test page. Check the configured printer name.');
        }
    }

    /** @return array<int, string> */
    private function availablePrinters(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        exec('powershell.exe -NoProfile -NonInteractive -Command "Get-CimInstance Win32_Printer | Select-Object -ExpandProperty Name"', $output, $exitCode);

        return $exitCode === 0
            ? collect($output)->map(fn (string $name): string => trim($name))->filter()->values()->all()
            : [];
    }

    private function powershellQuote(string $value): string
    {
        return "'".str_replace("'", "''", trim($value))."'";
    }
}
