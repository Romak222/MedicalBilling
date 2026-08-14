<x-app-shell page-title="Payment Reconciliation" section-label="Settlement Control">
    <x-slot:actions>
        <a href="{{ route('accounting.index') }}" class="btn-secondary">Accounting</a>
        <a href="{{ route('status') }}" class="btn-primary">Dashboard</a>
    </x-slot>

    <div class="space-y-5">
        @if (session('status'))
            <div class="surface-panel border-medical-100 bg-medical-50 px-4 py-3 text-sm font-semibold text-medical-800">{{ session('status') }}</div>
        @endif

        <section class="surface-panel p-5">
            <div class="border-b border-slate-200 pb-4">
                <p class="section-kicker">Provider settlement</p>
                <h2 class="mt-1 text-lg font-semibold text-ink-950">Reconcile card and UPI receipts</h2>
                <p class="mt-1 text-sm text-slate-600">The expected amount comes from finalized bills in the selected period. Settlement plus provider fee must match it exactly.</p>
            </div>

            @if ($canManage)
                <form wire:submit="save" class="mt-5 space-y-5">
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="block"><span class="field-label">Payment Method</span><select wire:model.live="paymentMethod" class="field-control mt-1"><option value="card">Card</option><option value="upi">UPI</option><option value="mixed">Mixed</option></select>@error('paymentMethod') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">Period From</span><input wire:model.live="periodFrom" type="date" class="field-control mt-1">@error('periodFrom') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">Period To</span><input wire:model.live="periodTo" type="date" class="field-control mt-1">@error('periodTo') <span class="field-error">{{ $message }}</span> @enderror</label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-medical-100 bg-medical-50 p-4"><p class="metric-label">Expected Amount</p><p class="mt-2 text-2xl font-semibold text-medical-700">{{ $expectedAmount }}</p><p class="mt-1 text-xs text-medical-800">Finalized {{ ucfirst($paymentMethod) }} bills</p></div>
                        <label class="block"><span class="field-label">Settled Amount</span><input wire:model="settledAmount" type="text" inputmode="decimal" class="field-control mt-1">@error('settledAmount') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">Provider Fee</span><input wire:model="feeAmount" type="text" inputmode="decimal" class="field-control mt-1">@error('feeAmount') <span class="field-error">{{ $message }}</span> @enderror</label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block"><span class="field-label">Settlement Date</span><input wire:model="settlementDate" type="date" class="field-control mt-1">@error('settlementDate') <span class="field-error">{{ $message }}</span> @enderror</label>
                        <label class="block"><span class="field-label">Settlement Reference</span><input wire:model="settlementReference" type="text" class="field-control mt-1 uppercase" placeholder="Provider settlement ID">@error('settlementReference') <span class="field-error">{{ $message }}</span> @enderror</label>
                    </div>

                    <label class="block"><span class="field-label">Notes</span><textarea wire:model="notes" rows="3" class="field-control mt-1"></textarea>@error('notes') <span class="field-error">{{ $message }}</span> @enderror</label>
                    <div class="flex justify-end border-t border-slate-200 pt-4"><button type="submit" class="btn-primary">Reconcile and Post</button></div>
                </form>
            @else
                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">You have view-only accounting access. A manager or owner must post settlement reconciliations.</div>
            @endif
        </section>

        <section class="surface-panel overflow-hidden">
            <div class="border-b border-slate-200 bg-white p-5"><p class="section-kicker">Settlement history</p><h2 class="mt-1 text-lg font-semibold text-ink-950">Posted reconciliations</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Method</th><th class="px-4 py-3">Period</th><th class="px-4 py-3 text-right">Expected</th><th class="px-4 py-3 text-right">Settled</th><th class="px-4 py-3 text-right">Fee</th><th class="px-4 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($reconciliations as $reconciliation)
                            <tr><td class="px-4 py-3"><a href="{{ route('accounting.reconciliation.show', $reconciliation) }}" class="font-semibold text-medical-700 hover:underline">{{ $reconciliation->settlement_reference }}</a><p class="mt-1 text-xs text-slate-500">{{ $reconciliation->reconciliation_number }}</p></td><td class="px-4 py-3 capitalize text-slate-700">{{ $reconciliation->payment_method }}</td><td class="px-4 py-3 text-slate-700">{{ $reconciliation->period_from?->format('d M Y') }} - {{ $reconciliation->period_to?->format('d M Y') }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $reconciliation->expected_amount }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $reconciliation->settled_amount }}</td><td class="px-4 py-3 text-right text-slate-700">{{ $reconciliation->fee_amount }}</td><td class="px-4 py-3"><span class="badge bg-medical-50 text-medical-700">{{ ucfirst($reconciliation->status) }}</span></td></tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm font-medium text-slate-500">No payment reconciliations have been posted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
