<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\HeldSalesBill;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Support\CashDrawerManager;
use App\Support\SalesBillingManager;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SalesInvoiceForm extends Component
{
    public string $quickScan = '';

    public string $quickMessage = '';

    public string $selectedHoldId = '';

    public array $sale = [
        'invoice_number' => '',
        'invoice_date' => '',
        'customer_id' => '',
        'patient_id' => '',
        'doctor_id' => '',
        'prescription_id' => '',
        'customer_name' => '',
        'customer_phone' => '',
        'patient_name' => '',
        'patient_phone' => '',
        'doctor_name' => '',
        'prescription_number' => '',
        'payment_method' => 'cash',
        'paid_amount' => '',
        'notes' => '',
    ];

    public array $items = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        $this->sale['invoice_date'] = today()->format('Y-m-d');
        $this->items = [$this->blankItem()];
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        $this->resetValidation();
        $this->sale['customer_id'] = $this->sale['customer_id'] === '' ? null : $this->sale['customer_id'];
        $this->sale['patient_id'] = $this->sale['patient_id'] === '' ? null : $this->sale['patient_id'];
        $this->sale['doctor_id'] = $this->sale['doctor_id'] === '' ? null : $this->sale['doctor_id'];
        $this->sale['prescription_id'] = $this->sale['prescription_id'] === '' ? null : $this->sale['prescription_id'];
        $validated = $this->validate();

        try {
            $invoice = app(SalesBillingManager::class)->createFinalizedInvoice($validated, auth()->user());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return null;
        }

        session()->flash('status', 'Sales invoice finalized.');

        return $this->redirectRoute('sales-invoices.show', $invoice, navigate: false);
    }

    public function holdBill(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        $payload = [
            'sale' => $this->sale,
            'items' => collect($this->items)
                ->filter(fn (array $item): bool => $item['product_batch_id'] !== '')
                ->values()
                ->all(),
        ];

        HeldSalesBill::query()->create([
            'hold_number' => 'HOLD-'.now()->format('Ymd-His'),
            'customer_name' => $this->sale['customer_name'] ?: ($this->sale['patient_name'] ?: null),
            'customer_phone' => $this->sale['customer_phone'] ?: null,
            'payload' => $payload,
            'created_by' => auth()->id(),
        ]);

        $this->resetBill();
        session()->flash('status', 'Bill held.');
    }

    public function resumeHold(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        if ($this->selectedHoldId === '') {
            return;
        }

        $heldBill = HeldSalesBill::query()->findOrFail($this->selectedHoldId);
        $payload = $heldBill->payload;

        $this->sale = array_merge($this->sale, $payload['sale'] ?? []);
        $this->items = $payload['items'] ?? [$this->blankItem()];
        $this->selectedHoldId = '';
        $heldBill->delete();

        session()->flash('status', 'Held bill resumed.');
    }

    public function discardHold(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        if ($this->selectedHoldId === '') {
            return;
        }

        HeldSalesBill::query()->findOrFail($this->selectedHoldId)->delete();
        $this->selectedHoldId = '';
        session()->flash('status', 'Held bill discarded.');
    }

    public function applyQuickScan(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.manage'), 403);

        $code = trim($this->quickScan);
        $this->quickMessage = '';
        $this->resetValidation(['sale.prescription_id']);

        if ($code === '') {
            return;
        }

        $barcode = ProductBarcode::query()->where('barcode', $code)->first();
        $batchQuery = ProductBatch::query()
            ->with(['product.taxRate'])
            ->where('available_quantity', '>', 0)
            ->where('is_blocked', false)
            ->where('expires_on', '>', today())
            ->orderBy('expires_on');

        if ($barcode) {
            $batchQuery->where('product_id', $barcode->product_id);
        } else {
            $batchQuery->where('batch_number', strtoupper($code));
        }

        $batch = $batchQuery->first();

        if (! $batch) {
            $this->quickMessage = 'No available non-expired batch found for this barcode or batch.';

            return;
        }

        if (($batch->product?->prescription_required || $batch->product?->controlled_medicine) && $this->sale['prescription_id'] === '') {
            $this->addError('sale.prescription_id', 'Select the prescription before scanning this product.');
            $this->quickMessage = 'Select the prescription before scanning this product.';

            return;
        }

        $lineIndex = collect($this->items)->search(fn (array $item): bool => (string) $item['product_batch_id'] === (string) $batch->id);
        $prescriptionItemId = $this->defaultPrescriptionItemIdForProduct($batch->product_id);

        if (($batch->product?->prescription_required || $batch->product?->controlled_medicine) && $prescriptionItemId === '') {
            $this->addError('sale.prescription_id', 'The selected prescription does not have an open line for this product.');
            $this->quickMessage = 'The selected prescription does not have an open line for this product.';

            return;
        }

        if ($lineIndex === false) {
            $this->items[] = [
                'product_batch_id' => (string) $batch->id,
                'prescription_item_id' => $prescriptionItemId,
                'quantity' => '1',
                'unit_price' => $batch->sale_rate ?? $batch->mrp,
                'discount_amount' => '0.00',
                'tax_rate_percent' => $batch->product?->taxRate?->rate_percent ?? '0.00',
            ];
        } else {
            $this->items[$lineIndex]['quantity'] = (string) ((float) $this->items[$lineIndex]['quantity'] + 1);
        }

        if (count($this->items) > 1 && $this->items[0]['product_batch_id'] === '') {
            array_shift($this->items);
        }

        $this->quickMessage = 'Added '.$batch->product?->name.' / '.$batch->batch_number;
        $this->quickScan = '';
        $this->sale['paid_amount'] = '';
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
    }

    public function updatedSaleDoctorId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->sale['doctor_name'] = '';

            return;
        }

        $doctor = Doctor::query()->findOrFail($value);
        $this->sale['doctor_name'] = $doctor->name;
    }

    public function updatedSaleCustomerId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $currentPatient = $this->sale['patient_id'] !== '' ? Patient::query()->find($this->sale['patient_id']) : null;

            if ($currentPatient?->customer_id) {
                $this->sale['patient_id'] = '';
                $this->sale['patient_name'] = '';
                $this->sale['patient_phone'] = '';
                $this->clearPrescriptionSelection();
            }

            $this->sale['customer_name'] = '';
            $this->sale['customer_phone'] = '';

            return;
        }

        $customer = Customer::query()->findOrFail($value);
        $this->sale['customer_name'] = $customer->name;
        $this->sale['customer_phone'] = $customer->phone ?? '';

        if ($this->sale['patient_id'] !== '') {
            $patient = Patient::query()->find($this->sale['patient_id']);

            if ($patient?->customer_id && $patient->customer_id !== $customer->id) {
                $this->sale['patient_id'] = '';
                $this->sale['patient_name'] = '';
                $this->sale['patient_phone'] = '';
                $this->clearPrescriptionSelection();
            }
        }
    }

    public function updatedSalePatientId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->sale['patient_name'] = '';
            $this->sale['patient_phone'] = '';
            $this->clearPrescriptionSelection(keepDoctor: true);

            return;
        }

        $patient = Patient::query()->with(['customer', 'doctor'])->findOrFail($value);
        $this->sale['patient_name'] = $patient->full_name;
        $this->sale['patient_phone'] = $patient->phone ?? '';

        if ($patient->customer) {
            $this->sale['customer_id'] = (string) $patient->customer->id;
            $this->sale['customer_name'] = $patient->customer->name;
            $this->sale['customer_phone'] = $patient->customer->phone ?? '';
        }

        if ($patient->doctor) {
            $this->sale['doctor_id'] = (string) $patient->doctor->id;
            $this->sale['doctor_name'] = $patient->doctor->name;
        } else {
            $this->sale['doctor_name'] = $patient->primary_doctor_name ?? '';
        }

        if ($this->sale['prescription_id'] !== '') {
            $prescription = Prescription::query()->find($this->sale['prescription_id']);

            if ($prescription?->patient_id !== $patient->id) {
                $this->clearPrescriptionSelection();
            }
        }
    }

    public function updatedSalePrescriptionId(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->sale['prescription_number'] = '';
            $this->reassignPrescriptionItems();

            return;
        }

        $prescription = Prescription::query()
            ->with(['patient.customer', 'doctor', 'items'])
            ->findOrFail($value);

        $this->sale['prescription_number'] = $prescription->prescription_number;
        $this->sale['patient_id'] = (string) $prescription->patient_id;
        $this->sale['patient_name'] = $prescription->patient_name_snapshot ?: $prescription->patient?->full_name ?: '';
        $this->sale['patient_phone'] = $prescription->patient_phone_snapshot ?: $prescription->patient?->phone ?: '';

        if ($prescription->patient?->customer) {
            $this->sale['customer_id'] = (string) $prescription->patient->customer->id;
            $this->sale['customer_name'] = $prescription->patient->customer->name;
            $this->sale['customer_phone'] = $prescription->patient->customer->phone ?? '';
        }

        if ($prescription->doctor) {
            $this->sale['doctor_id'] = (string) $prescription->doctor->id;
            $this->sale['doctor_name'] = $prescription->doctor->name;
        } else {
            $this->sale['doctor_name'] = $prescription->doctor_name_snapshot ?? '';
        }

        $this->reassignPrescriptionItems();
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) === 1) {
            $this->items[0] = $this->blankItem();

            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function useBatch(int $index): void
    {
        if (! isset($this->items[$index]) || $this->items[$index]['product_batch_id'] === '') {
            return;
        }

        $batch = ProductBatch::query()->with(['product.taxRate'])->findOrFail($this->items[$index]['product_batch_id']);

        $this->items[$index]['unit_price'] = $batch->sale_rate ?? $batch->mrp;
        $this->items[$index]['tax_rate_percent'] = $batch->product?->taxRate?->rate_percent ?? '0.00';
        $this->items[$index]['prescription_item_id'] = $this->defaultPrescriptionItemIdForProduct($batch->product_id);

        if (($batch->product?->prescription_required || $batch->product?->controlled_medicine) && $this->sale['prescription_id'] === '') {
            $this->addError('sale.prescription_id', 'Select a prescription before adding this product line.');
        }
    }

    public function render()
    {
        $previewTotals = $this->previewTotals();
        $prescriptions = Prescription::query()
            ->with(['patient.customer', 'doctor'])
            ->where('is_active', true)
            ->whereIn('status', [Prescription::STATUS_OPEN, Prescription::STATUS_PARTIAL])
            ->when($this->sale['patient_id'] !== '', fn ($query) => $query->where('patient_id', $this->sale['patient_id']))
            ->orderByDesc('prescription_date')
            ->get();
        $selectedPrescription = $this->sale['prescription_id'] !== ''
            ? Prescription::query()->with(['items.product'])->find($this->sale['prescription_id'])
            : null;

        if ($this->sale['paid_amount'] === '') {
            $this->sale['paid_amount'] = number_format($previewTotals['total'], 2, '.', '');
        }

        return view('livewire.sales-invoice-form', [
            'batches' => ProductBatch::query()
                ->with('product')
                ->where('available_quantity', '>', 0)
                ->where('is_blocked', false)
                ->where('expires_on', '>', today())
                ->orderBy('expires_on')
                ->get(),
            'customers' => Customer::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'doctors' => Doctor::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'heldBills' => HeldSalesBill::query()->latest()->limit(20)->get(),
            'patients' => Patient::query()
                ->with(['customer', 'doctor'])
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(),
            'prescriptions' => $prescriptions,
            'prescriptionItems' => $selectedPrescription?->items ?? collect(),
            'previewTotals' => $previewTotals,
            'cashDrawerShift' => app(CashDrawerManager::class)->currentOpen(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'sale.invoice_number' => ['nullable', 'string', 'max:80', Rule::unique('sales_invoices', 'invoice_number')],
            'sale.invoice_date' => ['required', 'date'],
            'sale.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'sale.patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'sale.doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'sale.prescription_id' => ['nullable', 'integer', 'exists:prescriptions,id'],
            'sale.customer_name' => ['nullable', 'string', 'max:180'],
            'sale.customer_phone' => ['nullable', 'string', 'max:40'],
            'sale.patient_name' => ['nullable', 'string', 'max:180'],
            'sale.patient_phone' => ['nullable', 'string', 'max:40'],
            'sale.doctor_name' => ['nullable', 'string', 'max:180'],
            'sale.prescription_number' => ['nullable', 'string', 'max:80'],
            'sale.payment_method' => ['required', 'string', 'max:40'],
            'sale.paid_amount' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'sale.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_batch_id' => ['required', 'integer', 'exists:product_batches,id'],
            'items.*.prescription_item_id' => ['nullable', 'integer', 'exists:prescription_items,id'],
            'items.*.quantity' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,5}(?:\.\d{1,6})?$/'],
            'items.*.unit_price' => ['required', 'regex:/^(?!0+(?:\.0+)?$)\d{1,7}(?:\.\d{1,2})?$/'],
            'items.*.discount_amount' => ['nullable', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'],
            'items.*.tax_rate_percent' => ['nullable', 'regex:/^\d{1,3}(?:\.\d{1,2})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function blankItem(): array
    {
        return [
            'product_batch_id' => '',
            'prescription_item_id' => '',
            'quantity' => '1',
            'unit_price' => '0.00',
            'discount_amount' => '0.00',
            'tax_rate_percent' => '0.00',
        ];
    }

    private function resetBill(): void
    {
        $this->quickScan = '';
        $this->quickMessage = '';
        $this->selectedHoldId = '';
        $this->sale = [
            'invoice_number' => '',
            'invoice_date' => today()->format('Y-m-d'),
            'customer_id' => '',
            'patient_id' => '',
            'doctor_id' => '',
            'prescription_id' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'patient_name' => '',
            'patient_phone' => '',
            'doctor_name' => '',
            'prescription_number' => '',
            'payment_method' => 'cash',
            'paid_amount' => '',
            'notes' => '',
        ];
        $this->items = [$this->blankItem()];
    }

    /**
     * @return array<string, float>
     */
    private function previewTotals(): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($this->items as $item) {
            $lineSubtotal = max(0, (float) ($item['quantity'] ?? 0)) * max(0, (float) ($item['unit_price'] ?? 0));
            $lineDiscount = min(max(0, (float) ($item['discount_amount'] ?? 0)), $lineSubtotal);
            $lineTaxable = max(0, $lineSubtotal - $lineDiscount);
            $lineTax = $lineTaxable * (max(0, (float) ($item['tax_rate_percent'] ?? 0)) / 100);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax += $lineTax;
            $total += $lineTaxable + $lineTax;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    private function clearPrescriptionSelection(bool $keepDoctor = false): void
    {
        $this->sale['prescription_id'] = '';
        $this->sale['prescription_number'] = '';

        if (! $keepDoctor) {
            $this->sale['doctor_id'] = '';
            $this->sale['doctor_name'] = '';
        }

        foreach ($this->items as $index => $item) {
            $this->items[$index]['prescription_item_id'] = '';
        }
    }

    private function reassignPrescriptionItems(): void
    {
        foreach ($this->items as $index => $item) {
            if (($item['product_batch_id'] ?? '') === '') {
                $this->items[$index]['prescription_item_id'] = '';

                continue;
            }

            $batch = ProductBatch::query()->find($item['product_batch_id']);
            $this->items[$index]['prescription_item_id'] = $batch ? $this->defaultPrescriptionItemIdForProduct($batch->product_id) : '';
        }
    }

    private function defaultPrescriptionItemIdForProduct(?int $productId): string
    {
        if (! $productId || $this->sale['prescription_id'] === '') {
            return '';
        }

        $prescription = Prescription::query()->with('items')->find($this->sale['prescription_id']);

        if (! $prescription) {
            return '';
        }

        $item = $prescription->items
            ->first(function (PrescriptionItem $item) use ($productId): bool {
                if ($item->product_id !== $productId) {
                    return false;
                }

                return $this->decimalToScaleInt($item->quantity_prescribed, 6) > $this->decimalToScaleInt($item->quantity_dispensed, 6);
            });

        return $item ? (string) $item->id : '';
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = trim((string) ($value ?? '0'));
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }
}
