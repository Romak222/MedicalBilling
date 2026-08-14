<?php

namespace App\Support;

use App\Models\ControlledMedicineRegisterEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;

class ControlledMedicineRegister
{
    public function recordSaleItem(SalesInvoice $invoice, SalesInvoiceItem $item, User $actor): ?ControlledMedicineRegisterEntry
    {
        $item->loadMissing(['product', 'productBatch']);

        if (! $this->shouldRecord($item->product)) {
            return null;
        }

        return ControlledMedicineRegisterEntry::query()->firstOrCreate(
            [
                'sales_invoice_item_id' => $item->id,
                'entry_type' => ControlledMedicineRegisterEntry::TYPE_SALE,
            ],
            $this->entryAttributes(
                invoice: $invoice,
                item: $item,
                actor: $actor,
                entryType: ControlledMedicineRegisterEntry::TYPE_SALE,
                quantityEffect: $this->normalizeSignedDecimal($item->quantity, 6),
                notes: 'Controlled medicine dispensed from bill '.$invoice->invoice_number.'.'
            )
        );
    }

    public function recordSaleCancellationItem(SalesInvoice $invoice, SalesInvoiceItem $item, User $actor): ?ControlledMedicineRegisterEntry
    {
        $item->loadMissing(['product', 'productBatch']);

        if (! $this->shouldRecord($item->product)) {
            return null;
        }

        return ControlledMedicineRegisterEntry::query()->firstOrCreate(
            [
                'sales_invoice_item_id' => $item->id,
                'entry_type' => ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL,
            ],
            $this->entryAttributes(
                invoice: $invoice,
                item: $item,
                actor: $actor,
                entryType: ControlledMedicineRegisterEntry::TYPE_SALE_CANCEL,
                quantityEffect: '-'.$this->normalizePositiveDecimal($item->quantity, 6),
                notes: 'Controlled medicine reversal from cancelled bill '.$invoice->invoice_number.'.'
            )
        );
    }

    public function recordReturnItem(SalesReturn $salesReturn, SalesReturnItem $item, User $actor): ?ControlledMedicineRegisterEntry
    {
        $salesReturn->loadMissing('salesInvoice');
        $item->loadMissing(['product', 'productBatch']);

        if (! $this->shouldRecord($item->product)) {
            return null;
        }

        return ControlledMedicineRegisterEntry::query()->firstOrCreate(
            [
                'sales_return_item_id' => $item->id,
                'entry_type' => ControlledMedicineRegisterEntry::TYPE_SALE_RETURN,
            ],
            $this->entryAttributes(
                invoice: $salesReturn->salesInvoice,
                item: $item,
                actor: $actor,
                entryType: ControlledMedicineRegisterEntry::TYPE_SALE_RETURN,
                quantityEffect: '-'.$this->normalizePositiveDecimal($item->quantity, 6),
                notes: 'Controlled medicine reversal from sales return '.$salesReturn->return_number.'.',
                salesReturn: $salesReturn
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function entryAttributes(
        SalesInvoice $invoice,
        SalesInvoiceItem|SalesReturnItem $item,
        User $actor,
        string $entryType,
        string $quantityEffect,
        string $notes,
        ?SalesReturn $salesReturn = null,
    ): array {
        return [
            'product_id' => $item->product_id,
            'product_batch_id' => $item->product_batch_id,
            'customer_id' => $invoice->customer_id,
            'patient_id' => $invoice->patient_id,
            'doctor_id' => $invoice->doctor_id,
            'prescription_id' => $invoice->prescription_id,
            'prescription_item_id' => $item->prescription_item_id,
            'sales_invoice_id' => $invoice->id,
            'sales_return_id' => $salesReturn?->id,
            'event_date' => ($salesReturn?->return_date ?? $invoice->invoice_date)?->toDateString() ?? today()->toDateString(),
            'quantity_effect' => $quantityEffect,
            'product_name_snapshot' => $item->product_name_snapshot,
            'batch_number_snapshot' => $item->batch_number_snapshot,
            'patient_name_snapshot' => $invoice->patient_name,
            'doctor_name_snapshot' => $invoice->doctor_name,
            'prescription_number_snapshot' => $invoice->prescription_number,
            'invoice_number_snapshot' => $invoice->invoice_number,
            'return_number_snapshot' => $salesReturn?->return_number,
            'notes' => $notes,
            'created_by' => $actor->id,
        ];
    }

    private function shouldRecord(?Product $product): bool
    {
        return (bool) $product?->controlled_medicine;
    }

    private function normalizeSignedDecimal(mixed $value, int $scale): string
    {
        $integer = $this->decimalToScaleInt($value, $scale);

        return $this->formatScaled($integer, $scale);
    }

    private function normalizePositiveDecimal(mixed $value, int $scale): string
    {
        return $this->formatScaled(abs($this->decimalToScaleInt($value, $scale)), $scale);
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = is_string($value) ? trim($value) : (string) $value;
        $value = $value === '' ? '0' : $value;
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }
}
