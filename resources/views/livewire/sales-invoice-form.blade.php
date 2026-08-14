<x-app-shell page-title="New Bill" section-label="POS Billing">
    <x-slot:actions>
        <a href="{{ route('sales-invoices.index') }}" class="btn-secondary">Back to Billing</a>
        <button type="button" wire:click="save" class="btn-primary">Finalize Bill</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($cashDrawerShift)
            <div class="surface-panel border-medical-100 bg-medical-50/60 px-4 py-3 text-sm text-medical-800">
                Cash payments will be assigned to active drawer shift <a href="{{ route('cash-drawer.show', $cashDrawerShift) }}" class="font-semibold underline-offset-2 hover:underline">{{ $cashDrawerShift->shift_number }}</a>.
            </div>
        @else
            <div class="surface-panel border-amber-200 bg-amber-50/60 px-4 py-3 text-sm text-amber-800">
                No cash drawer shift is open. Cash bills can be finalized, but this cash activity will remain unassigned until drawer controls are active.
                @if (auth()->user()?->hasPermission('cash_drawer.manage'))
                    <a href="{{ route('cash-drawer.index') }}" class="ml-1 font-semibold underline-offset-2 hover:underline">Open cash drawer</a>
                @endif
            </div>
        @endif

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Bill Header</p>
                <h2 class="text-lg font-semibold text-ink-950">Customer, Patient, Prescription and Payment</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Barcode or Batch Quick Scan</span>
                    <div class="mt-1 flex gap-2">
                        <input wire:model="quickScan" wire:keydown.enter.prevent="applyQuickScan" type="text" class="field-control" placeholder="Scan barcode or enter batch number">
                        <button type="button" wire:click="applyQuickScan" class="btn-secondary shrink-0">Add</button>
                    </div>
                    @if ($quickMessage)
                        <p class="mt-2 text-xs font-semibold text-medical-700">{{ $quickMessage }}</p>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Held Bills</span>
                    <div class="mt-1 flex gap-2">
                        <select wire:model="selectedHoldId" class="field-control">
                            <option value="">Choose held bill</option>
                            @foreach ($heldBills as $held)
                                <option value="{{ $held->id }}">{{ $held->hold_number }} / {{ $held->customer_name ?: 'Walk-in' }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="resumeHold" class="btn-secondary shrink-0">Resume</button>
                        <button type="button" wire:click="discardHold" class="btn-secondary shrink-0">Discard</button>
                    </div>
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Customer Record</span>
                    <select wire:model="sale.customer_id" class="field-control mt-1">
                        <option value="">Walk-in or manual customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">
                                {{ $customer->name }}{{ $customer->phone ? ' / '.$customer->phone : '' }}{{ $customer->code ? ' / '.$customer->code : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sale.customer_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Patient Record</span>
                    <select wire:model="sale.patient_id" class="field-control mt-1">
                        <option value="">No patient record</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">
                                {{ $patient->full_name }}{{ $patient->customer ? ' / '.$patient->customer->name : '' }}{{ $patient->phone ? ' / '.$patient->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sale.patient_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Doctor Record</span>
                    <select wire:model="sale.doctor_id" class="field-control mt-1">
                        <option value="">No doctor record</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}">
                                {{ $doctor->name }}{{ $doctor->registration_number ? ' / '.$doctor->registration_number : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('sale.doctor_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Prescription Record</span>
                    <select wire:model="sale.prescription_id" class="field-control mt-1">
                        <option value="">No linked prescription</option>
                        @foreach ($prescriptions as $prescription)
                            <option value="{{ $prescription->id }}">
                                {{ $prescription->prescription_number }} / {{ $prescription->patient_name_snapshot ?: $prescription->patient?->full_name }}{{ $prescription->doctor_name_snapshot ? ' / '.$prescription->doctor_name_snapshot : ($prescription->doctor?->name ? ' / '.$prescription->doctor->name : '') }}
                            </option>
                        @endforeach
                    </select>
                    @error('sale.prescription_id') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Invoice Number</span>
                    <input wire:model="sale.invoice_number" type="text" class="field-control mt-1 uppercase" placeholder="Auto if blank">
                    @error('sale.invoice_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Bill Date</span>
                    <input wire:model="sale.invoice_date" type="date" class="field-control mt-1">
                    @error('sale.invoice_date') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Customer Name</span>
                    <input wire:model="sale.customer_name" type="text" class="field-control mt-1">
                    @error('sale.customer_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Customer Phone</span>
                    <input wire:model="sale.customer_phone" type="text" class="field-control mt-1">
                    @error('sale.customer_phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Patient Name</span>
                    <input wire:model="sale.patient_name" type="text" class="field-control mt-1">
                    @error('sale.patient_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Patient Phone</span>
                    <input wire:model="sale.patient_phone" type="text" class="field-control mt-1">
                    @error('sale.patient_phone') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Doctor Name</span>
                    <input wire:model="sale.doctor_name" type="text" class="field-control mt-1">
                    @error('sale.doctor_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Prescription Number</span>
                    <input wire:model="sale.prescription_number" type="text" class="field-control mt-1 uppercase">
                    @error('sale.prescription_number') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Payment Method</span>
                    <select wire:model="sale.payment_method" class="field-control mt-1">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="mixed">Mixed</option>
                    </select>
                    @error('sale.payment_method') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Paid Amount</span>
                    <input wire:model="sale.paid_amount" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('sale.paid_amount') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Notes</span>
                    <input wire:model="sale.notes" type="text" class="field-control mt-1">
                    @error('sale.notes') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-kicker">Bill Items</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink-950">Batch, Quantity and Price</h2>
                </div>
                <button type="button" wire:click="addItem" class="btn-secondary">Add Line</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-4 py-3">RX Line</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Discount</th>
                            <th class="px-4 py-3">Tax %</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($items as $index => $item)
                            <tr wire:key="sales-invoice-item-{{ $index }}">
                                <td class="min-w-96 px-4 py-3 align-top">
                                    <select wire:model="items.{{ $index }}.product_batch_id" wire:change="useBatch({{ $index }})" class="field-control">
                                        <option value="">Choose available batch</option>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->product?->name }} / {{ $batch->batch_number }} / Exp {{ $batch->expires_on->format('d M Y') }} / Qty {{ $batch->available_quantity }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.$index.product_batch_id") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-72 px-4 py-3 align-top">
                                    <select wire:model="items.{{ $index }}.prescription_item_id" class="field-control">
                                        <option value="">No prescription line</option>
                                        @foreach ($prescriptionItems as $prescriptionItem)
                                            <option value="{{ $prescriptionItem->id }}">
                                                {{ $prescriptionItem->medicine_name_snapshot }}
                                                / Remaining {{ number_format((float) $prescriptionItem->quantity_prescribed - (float) $prescriptionItem->quantity_dispensed, 6, '.', '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.$index.prescription_item_id") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-28 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.quantity" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.quantity") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.unit_price" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.unit_price") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-32 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.discount_amount" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.discount_amount") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="min-w-28 px-4 py-3 align-top">
                                    <input wire:model.live="items.{{ $index }}.tax_rate_percent" type="text" inputmode="decimal" class="field-control">
                                    @error("items.$index.tax_rate_percent") <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    <button type="button" wire:click="removeItem({{ $index }})" class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Subtotal</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['subtotal'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Discount</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['discount'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="metric-label">Tax</p>
                    <p class="mt-2 text-xl font-semibold text-ink-950">{{ number_format($previewTotals['tax'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-medical-100 bg-medical-50 p-4">
                    <p class="metric-label text-medical-700">Total</p>
                    <p class="mt-2 text-xl font-semibold text-medical-800">{{ number_format($previewTotals['total'], 2) }}</p>
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('sales-invoices.index') }}" class="btn-secondary">Cancel</a>
            <button type="button" wire:click="holdBill" class="btn-secondary">Hold Bill</button>
            <button type="submit" class="btn-primary">Finalize Bill</button>
        </div>
    </form>
</x-app-shell>
