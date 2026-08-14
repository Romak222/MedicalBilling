<x-layouts.app :title="config('app.name').' Receipt'">
    <div class="min-h-screen bg-white px-4 py-6 text-ink-950">
        <div class="mx-auto border border-slate-300 bg-white p-4 text-sm shadow-sm print:border-0 print:shadow-none" style="width: {{ in_array($receiptPaperWidth, [58, 80], true) ? $receiptPaperWidth : 80 }}mm; max-width: 100%;">
            <div class="border-b border-slate-300 pb-3 text-center">
                <p class="text-lg font-bold">{{ $store?->name ?: config('app.name') }}</p>
                <p class="mt-1 text-xs text-slate-600">{{ $store?->code ?: config('pharmacy.store_code') }}</p>
                @if ($store?->phone || $store?->email)
                    <p class="mt-1 text-[11px] text-slate-600">{{ collect([$store?->phone, $store?->email])->filter()->join(' | ') }}</p>
                @endif
            </div>

            <div class="space-y-1 border-b border-slate-300 py-3 text-xs">
                <p><span class="font-semibold">Bill:</span> {{ $salesInvoice->invoice_number }}</p>
                <p><span class="font-semibold">Date:</span> {{ $salesInvoice->invoice_date?->format('d M Y') }}</p>
                <p><span class="font-semibold">Customer:</span> {{ $salesInvoice->customer_name ?: ($salesInvoice->patient_name ?: 'Walk-in customer') }}</p>
                @if ($salesInvoice->patient_name)
                    <p><span class="font-semibold">Patient:</span> {{ $salesInvoice->patient_name }}</p>
                @endif
                @if ($salesInvoice->doctor_name)
                    <p><span class="font-semibold">Doctor:</span> {{ $salesInvoice->doctor_name }}</p>
                @endif
                @if ($salesInvoice->prescription_number)
                    <p><span class="font-semibold">Prescription:</span> {{ $salesInvoice->prescription_number }}</p>
                @endif
                <p><span class="font-semibold">Payment:</span> {{ ucfirst($salesInvoice->payment_method ?: 'Not set') }}</p>
            </div>

            <table class="w-full border-b border-slate-300 py-2 text-xs">
                <thead>
                    <tr class="text-left">
                        <th class="py-2">Item</th>
                        <th class="py-2 text-right">Qty</th>
                        <th class="py-2 text-right">Amt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesInvoice->items as $item)
                        <tr>
                            <td class="py-1 pr-2">
                                <p class="font-semibold">{{ $item->product_name_snapshot }}</p>
                                <p class="text-[11px] text-slate-600">Batch {{ $item->batch_number_snapshot }} / Exp {{ $item->expires_on_snapshot?->format('m/Y') }}</p>
                            </td>
                            <td class="py-1 text-right">{{ $item->quantity }}</td>
                            <td class="py-1 text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="space-y-1 border-b border-slate-300 py-3 text-xs">
                <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format((float) $salesInvoice->subtotal_amount, 2) }}</span></div>
                <div class="flex justify-between"><span>Discount</span><span>{{ number_format((float) $salesInvoice->discount_amount, 2) }}</span></div>
                <div class="flex justify-between"><span>Tax</span><span>{{ number_format((float) $salesInvoice->tax_amount, 2) }}</span></div>
                <div class="flex justify-between text-sm font-bold"><span>Total</span><span>{{ number_format((float) $salesInvoice->total_amount, 2) }}</span></div>
                <div class="flex justify-between"><span>Paid</span><span>{{ number_format((float) $salesInvoice->paid_amount, 2) }}</span></div>
                <div class="flex justify-between"><span>Change</span><span>{{ number_format((float) $salesInvoice->change_amount, 2) }}</span></div>
            </div>

            <p class="whitespace-pre-line pt-3 text-center text-xs font-semibold">{{ $receiptFooter ?: 'Thank you for visiting.' }}</p>

            <div class="mt-4 flex justify-center gap-2 print:hidden">
                <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="btn-secondary">Back</a>
                <button type="button" onclick="window.print()" class="btn-primary">Print</button>
            </div>
        </div>
    </div>
</x-layouts.app>
