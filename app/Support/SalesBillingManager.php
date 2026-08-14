<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesBillingManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFinalizedInvoice(array $payload, User $actor): SalesInvoice
    {
        return DB::transaction(function () use ($payload, $actor): SalesInvoice {
            $customer = $this->customerRecord(Arr::get($payload, 'sale.customer_id'));
            $patient = $this->patientRecord(Arr::get($payload, 'sale.patient_id'));
            $doctor = $this->doctorRecord(Arr::get($payload, 'sale.doctor_id'));
            $prescription = $this->prescriptionRecord(Arr::get($payload, 'sale.prescription_id'));

            if ($prescription && ! $prescription->is_active) {
                $this->validationFailure('sale.prescription_id', 'Selected prescription is archived.');
            }

            if (! $patient && $prescription?->patient) {
                $patient = $prescription->patient;
            }

            if (! $doctor && $prescription?->doctor) {
                $doctor = $prescription->doctor;
            }

            if (! $doctor && $patient?->doctor) {
                $doctor = $patient->doctor;
            }

            if (! $customer && $patient?->customer) {
                $customer = $patient->customer;
            }

            if ($customer && $patient?->customer_id && $patient->customer_id !== $customer->id) {
                $this->validationFailure('sale.patient_id', 'Selected patient does not belong to the selected customer.');
            }

            if ($prescription && $patient && $prescription->patient_id !== $patient->id) {
                $this->validationFailure('sale.prescription_id', 'Selected prescription does not belong to the selected patient.');
            }

            if ($prescription && $doctor && $prescription->doctor_id && $prescription->doctor_id !== $doctor->id) {
                $this->validationFailure('sale.prescription_id', 'Selected prescription does not belong to the selected doctor.');
            }

            $lines = $this->lineAttributes(Arr::get($payload, 'items', []), $prescription);
            $totals = $this->totals($lines);
            $paidAmount = $this->decimalOrZero(Arr::get($payload, 'sale.paid_amount', $totals['total_amount']));
            $changeAmount = max(0, $this->decimalToScaleInt($paidAmount, 2) - $this->decimalToScaleInt($totals['total_amount'], 2));
            $paymentMethod = $this->blankToNull(Arr::get($payload, 'sale.payment_method'));
            $cashDrawerShift = strtolower((string) $paymentMethod) === 'cash'
                ? app(CashDrawerManager::class)->currentOpen()
                : null;

            $invoice = SalesInvoice::query()->create(array_merge($totals, [
                'invoice_number' => strtoupper($this->blankToNull(Arr::get($payload, 'sale.invoice_number')) ?: $this->nextInvoiceNumber()),
                'invoice_date' => $this->blankToNull(Arr::get($payload, 'sale.invoice_date')) ?: today()->toDateString(),
                'customer_id' => $customer?->id,
                'patient_id' => $patient?->id,
                'doctor_id' => $doctor?->id,
                'prescription_id' => $prescription?->id,
                'customer_name' => $this->blankToNull(Arr::get($payload, 'sale.customer_name')) ?? $customer?->name,
                'customer_phone' => $this->blankToNull(Arr::get($payload, 'sale.customer_phone')) ?? $customer?->phone,
                'patient_name' => $this->blankToNull(Arr::get($payload, 'sale.patient_name')) ?? $patient?->full_name,
                'patient_phone' => $this->blankToNull(Arr::get($payload, 'sale.patient_phone')) ?? $patient?->phone,
                'doctor_name' => $this->blankToNull(Arr::get($payload, 'sale.doctor_name')) ?? $doctor?->name ?? $prescription?->doctor_name_snapshot ?? $patient?->primary_doctor_name,
                'prescription_number' => $this->blankToNull(Arr::get($payload, 'sale.prescription_number')) ?? $prescription?->prescription_number,
                'status' => SalesInvoice::STATUS_FINALIZED,
                'payment_method' => $paymentMethod,
                'cash_drawer_shift_id' => $cashDrawerShift?->id,
                'paid_amount' => $paidAmount,
                'change_amount' => $this->formatScaled($changeAmount, 2),
                'notes' => $this->blankToNull(Arr::get($payload, 'sale.notes')),
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]));

            foreach ($lines as $line) {
                $item = $invoice->items()->create($line);
                $batch = ProductBatch::query()->findOrFail($line['product_batch_id']);

                $batch->update([
                    'available_quantity' => $this->subtractDecimals($batch->available_quantity, $line['quantity'], 6),
                    'updated_by' => $actor->id,
                ]);

                StockMovement::query()->create([
                    'product_id' => $line['product_id'],
                    'product_batch_id' => $line['product_batch_id'],
                    'movement_type' => StockMovement::TYPE_SALE,
                    'source_type' => SalesInvoice::class,
                    'source_id' => $invoice->id,
                    'quantity' => '-'.$this->normalizePositiveDecimal($line['quantity'], 6),
                    'unit_cost' => $line['unit_price'],
                    'notes' => 'Sales invoice '.$invoice->invoice_number.' item '.$item->id,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                ]);

                if ($line['prescription_item_id']) {
                    app(PrescriptionRegistry::class)->incrementDispensedQuantity(
                        PrescriptionItem::query()->findOrFail($line['prescription_item_id']),
                        $line['quantity']
                    );
                }

                app(ControlledMedicineRegister::class)->recordSaleItem($invoice, $item, $actor);
            }

            app(AccountingManager::class)->postSale($invoice, $actor);

            app(AuditLogger::class)->record('sales_invoice.created', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh()->load(['customer', 'patient', 'doctor', 'prescription', 'items.product', 'items.productBatch', 'items.prescriptionItem']);
        });
    }

    public function cancelInvoice(SalesInvoice $invoice, User $actor): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $actor): SalesInvoice {
            abort_unless($invoice->status === SalesInvoice::STATUS_FINALIZED, 422, 'Only finalized sales invoices can be cancelled.');
            abort_if($invoice->salesReturns()->exists(), 422, 'Sales invoices with recorded returns cannot be cancelled.');

            $invoice->load('items.productBatch', 'items.prescriptionItem');
            $invoice->update([
                'status' => SalesInvoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($invoice->items as $item) {
                $batch = ProductBatch::query()->findOrFail($item->product_batch_id);
                $batch->update([
                    'available_quantity' => $this->addDecimals($batch->available_quantity, $item->quantity, 6),
                    'updated_by' => $actor->id,
                ]);

                StockMovement::query()->create([
                    'product_id' => $item->product_id,
                    'product_batch_id' => $item->product_batch_id,
                    'movement_type' => StockMovement::TYPE_SALE_CANCEL,
                    'source_type' => SalesInvoice::class,
                    'source_id' => $invoice->id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_price,
                    'notes' => 'Cancelled sales invoice '.$invoice->invoice_number,
                    'created_by' => $actor->id,
                    'occurred_at' => now(),
                ]);

                if ($item->prescriptionItem) {
                    app(PrescriptionRegistry::class)->decrementDispensedQuantity($item->prescriptionItem, $item->quantity);
                }

                app(ControlledMedicineRegister::class)->recordSaleCancellationItem($invoice, $item, $actor);
            }

            app(AccountingManager::class)->reverseSale($invoice, $actor);

            app(AuditLogger::class)->record('sales_invoice.cancelled', $actor, $invoice, $this->auditMetadata($invoice));

            return $invoice->refresh()->load(['customer', 'patient', 'doctor', 'prescription', 'items.product', 'items.productBatch', 'items.prescriptionItem']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function lineAttributes(array $items, ?Prescription $prescription): array
    {
        $plannedDispenseByPrescriptionItem = [];

        return collect($items)
            ->map(function (array $item, int $index) use ($prescription, &$plannedDispenseByPrescriptionItem): array {
                $batch = ProductBatch::query()
                    ->with(['product.taxRate', 'product.baseUnit'])
                    ->where('available_quantity', '>', 0)
                    ->where('is_blocked', false)
                    ->where('expires_on', '>', today())
                    ->find(Arr::get($item, 'product_batch_id'));

                if (! $batch) {
                    $this->validationFailure("items.$index.product_batch_id", 'Selected batch is no longer available for sale.');
                }

                $quantity = $this->decimalOrZero(Arr::get($item, 'quantity'));
                if ($this->decimalToScaleInt($quantity, 6) <= 0) {
                    $this->validationFailure("items.$index.quantity", 'Sale quantity must be greater than zero.');
                }

                if ($this->decimalToScaleInt($batch->available_quantity, 6) < $this->decimalToScaleInt($quantity, 6)) {
                    $this->validationFailure("items.$index.quantity", 'Insufficient batch quantity.');
                }

                $prescriptionItem = $this->prescriptionItemRecord(Arr::get($item, 'prescription_item_id'), $prescription, $index);
                $requiresPrescription = (bool) ($batch->product?->prescription_required || $batch->product?->controlled_medicine);

                if ($requiresPrescription && (! $prescription || ! $prescriptionItem)) {
                    $this->validationFailureMessages([
                        'sale.prescription_id' => 'Select a linked prescription before billing prescription-linked or controlled products.',
                        "items.$index.prescription_item_id" => 'Select the matching prescription line for this product.',
                    ]);
                }

                if ($prescriptionItem) {
                    if (! $prescription) {
                        $this->validationFailure('sale.prescription_id', 'Select the prescription header before linking prescription items.');
                    }

                    if ($prescriptionItem->product_id && $prescriptionItem->product_id !== $batch->product_id) {
                        $this->validationFailure("items.$index.prescription_item_id", 'The selected prescription line does not match the billed product.');
                    }

                    $remainingPrescribed = $this->decimalToScaleInt($prescriptionItem->quantity_prescribed, 6)
                        - $this->decimalToScaleInt($prescriptionItem->quantity_dispensed, 6);
                    $plannedMicros = $plannedDispenseByPrescriptionItem[$prescriptionItem->id] ?? 0;

                    if ($remainingPrescribed <= 0) {
                        $this->validationFailure("items.$index.prescription_item_id", 'The selected prescription line has no remaining quantity to dispense.');
                    }

                    if ($plannedMicros + $this->decimalToScaleInt($quantity, 6) > $remainingPrescribed) {
                        $this->validationFailure("items.$index.quantity", 'Billed quantity exceeds the remaining prescribed quantity.');
                    }

                    $plannedDispenseByPrescriptionItem[$prescriptionItem->id] = $plannedMicros + $this->decimalToScaleInt($quantity, 6);
                }

                $unitPrice = $this->decimalOrZero(Arr::get($item, 'unit_price', $batch->sale_rate ?? $batch->mrp));
                $discountAmount = $this->decimalOrZero(Arr::get($item, 'discount_amount'));
                $taxRatePercent = $this->decimalOrZero(Arr::get($item, 'tax_rate_percent', $batch->product?->taxRate?->rate_percent ?? '0'));
                $lineTotals = $this->lineTotals($quantity, $unitPrice, $discountAmount, $taxRatePercent);

                return array_merge([
                    'product_id' => $batch->product_id,
                    'product_batch_id' => $batch->id,
                    'prescription_item_id' => $prescriptionItem?->id,
                    'product_name_snapshot' => $batch->product?->name ?: 'Product',
                    'batch_number_snapshot' => $batch->batch_number,
                    'expires_on_snapshot' => $batch->expires_on,
                    'unit_name' => $batch->product?->baseUnit?->unit_name ?: 'Unit',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'tax_rate_percent' => $taxRatePercent,
                ], $lineTotals);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, string>
     */
    private function totals(array $lines): array
    {
        $subtotalCents = 0;
        $discountCents = 0;
        $taxCents = 0;
        $totalCents = 0;

        foreach ($lines as $line) {
            $subtotalCents += $this->decimalToScaleInt($line['line_subtotal'], 2);
            $discountCents += min(
                $this->decimalToScaleInt($line['discount_amount'], 2),
                $this->decimalToScaleInt($line['line_subtotal'], 2)
            );
            $taxCents += $this->decimalToScaleInt($line['line_tax'], 2);
            $totalCents += $this->decimalToScaleInt($line['line_total'], 2);
        }

        return [
            'subtotal_amount' => $this->formatScaled($subtotalCents, 2),
            'discount_amount' => $this->formatScaled($discountCents, 2),
            'tax_amount' => $this->formatScaled($taxCents, 2),
            'total_amount' => $this->formatScaled($totalCents, 2),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function lineTotals(string $quantity, string $unitPrice, string $discountAmount, string $taxRatePercent): array
    {
        $quantityMicros = $this->decimalToScaleInt($quantity, 6);
        $unitPriceCents = $this->decimalToScaleInt($unitPrice, 2);
        $lineSubtotalCents = intdiv(($quantityMicros * $unitPriceCents) + 500000, 1000000);
        $discountCents = min($this->decimalToScaleInt($discountAmount, 2), $lineSubtotalCents);
        $taxableCents = max(0, $lineSubtotalCents - $discountCents);
        $taxBasisPoints = $this->decimalToScaleInt($taxRatePercent, 2);
        $lineTaxCents = intdiv(($taxableCents * $taxBasisPoints) + 5000, 10000);
        $lineTotalCents = $taxableCents + $lineTaxCents;

        return [
            'line_subtotal' => $this->formatScaled($lineSubtotalCents, 2),
            'line_tax' => $this->formatScaled($lineTaxCents, 2),
            'line_total' => $this->formatScaled($lineTotalCents, 2),
        ];
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'SI-'.now()->format('Ymd');
        $next = SalesInvoice::query()
            ->where('invoice_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(SalesInvoice $invoice): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'total_amount' => $invoice->total_amount,
            'payment_method' => $invoice->payment_method,
            'cash_drawer_shift_id' => $invoice->cash_drawer_shift_id,
            'customer_id' => $invoice->customer_id,
            'patient_id' => $invoice->patient_id,
            'doctor_id' => $invoice->doctor_id,
            'prescription_id' => $invoice->prescription_id,
        ];
    }

    private function customerRecord(mixed $customerId): ?Customer
    {
        $customerId = $this->blankToNull($customerId);

        if ($customerId === null) {
            return null;
        }

        $customer = Customer::query()->find($customerId);

        if (! $customer) {
            $this->validationFailure('sale.customer_id', 'Selected customer record is no longer available.');
        }

        return $customer;
    }

    private function patientRecord(mixed $patientId): ?Patient
    {
        $patientId = $this->blankToNull($patientId);

        if ($patientId === null) {
            return null;
        }

        $patient = Patient::query()->with(['customer', 'doctor'])->find($patientId);

        if (! $patient) {
            $this->validationFailure('sale.patient_id', 'Selected patient record is no longer available.');
        }

        return $patient;
    }

    private function doctorRecord(mixed $doctorId): ?Doctor
    {
        $doctorId = $this->blankToNull($doctorId);

        if ($doctorId === null) {
            return null;
        }

        $doctor = Doctor::query()->find($doctorId);

        if (! $doctor) {
            $this->validationFailure('sale.doctor_id', 'Selected doctor record is no longer available.');
        }

        return $doctor;
    }

    private function prescriptionRecord(mixed $prescriptionId): ?Prescription
    {
        $prescriptionId = $this->blankToNull($prescriptionId);

        if ($prescriptionId === null) {
            return null;
        }

        $prescription = Prescription::query()->with(['patient.customer', 'doctor', 'items'])->find($prescriptionId);

        if (! $prescription) {
            $this->validationFailure('sale.prescription_id', 'Selected prescription record is no longer available.');
        }

        return $prescription;
    }

    private function prescriptionItemRecord(mixed $prescriptionItemId, ?Prescription $prescription, int $index): ?PrescriptionItem
    {
        $prescriptionItemId = $this->blankToNull($prescriptionItemId);

        if ($prescriptionItemId === null) {
            return null;
        }

        $item = PrescriptionItem::query()->find($prescriptionItemId);

        if (! $item) {
            $this->validationFailure("items.$index.prescription_item_id", 'Selected prescription line is no longer available.');
        }

        if ($prescription) {
            if ($item->prescription_id !== $prescription->id) {
                $this->validationFailure("items.$index.prescription_item_id", 'The selected prescription line does not belong to the selected prescription.');
            }
        }

        return $item;
    }

    private function validationFailure(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }

    /**
     * @param  array<string, string|array<int, string>>  $messages
     */
    private function validationFailureMessages(array $messages): never
    {
        throw ValidationException::withMessages($messages);
    }

    private function addDecimals(mixed $left, mixed $right, int $scale = 2): string
    {
        return $this->formatScaled(
            $this->decimalToScaleInt($left, $scale) + $this->decimalToScaleInt($right, $scale),
            $scale
        );
    }

    private function subtractDecimals(mixed $left, mixed $right, int $scale = 2): string
    {
        return $this->formatScaled(
            $this->decimalToScaleInt($left, $scale) - $this->decimalToScaleInt($right, $scale),
            $scale
        );
    }

    private function normalizePositiveDecimal(mixed $value, int $scale): string
    {
        return $this->formatScaled(abs($this->decimalToScaleInt($value, $scale)), $scale);
    }

    private function decimalOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = (string) ($this->blankToNull($value) ?? '0');
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

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
