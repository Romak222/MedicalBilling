<x-app-shell page-title="Cash Drawer" section-label="Shift Control">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif
        <a href="{{ route('cash-drawer.index') }}" class="btn-secondary">Refresh</a>
    </x-slot>

    <div class="space-y-5">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="metric-tile border-t-4 border-t-medical-600">
                <p class="metric-label">Open Shifts</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-medical-700">{{ $stats['open'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-care-600">
                <p class="metric-label">Closed Today</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-care-700">{{ $stats['closed_today'] }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-pharma-600">
                <p class="metric-label">Cash Sales Today</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal text-pharma-700">{{ number_format((float) $stats['cash_sales_today'], 2) }}</p>
            </div>
            <div class="metric-tile border-t-4 border-t-amber-500">
                <p class="metric-label">Variance Today</p>
                <p class="mt-2 text-3xl font-semibold tracking-normal {{ (float) $stats['variance_today'] === 0.0 ? 'text-ink-900' : 'text-amber-700' }}">{{ number_format((float) $stats['variance_today'], 2) }}</p>
            </div>
        </section>

        @if ($currentShift)
            <section class="surface-panel overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-medical-100 bg-medical-50/60 p-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="section-kicker text-medical-700">Active Shift</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $currentShift->shift_number }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Opened {{ $currentShift->opened_at?->format('d M Y, h:i A') }} by {{ $currentShift->openedBy?->name }}</p>
                    </div>
                    <span class="badge bg-medical-100 text-medical-800">Open</span>
                </div>

                <div class="grid gap-3 border-b border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="metric-tile"><p class="metric-label">Opening Float</p><p class="mt-2 text-lg font-semibold text-ink-950">{{ number_format((float) $currentTotals['opening_float'], 2) }}</p></div>
                    <div class="metric-tile"><p class="metric-label">Cash Sales</p><p class="mt-2 text-lg font-semibold text-medical-700">{{ number_format((float) $currentTotals['cash_sales_amount'], 2) }}</p></div>
                    <div class="metric-tile"><p class="metric-label">Cash Refunds</p><p class="mt-2 text-lg font-semibold text-red-700">{{ number_format((float) $currentTotals['cash_refunds_amount'], 2) }}</p></div>
                    <div class="metric-tile"><p class="metric-label">Cash In</p><p class="mt-2 text-lg font-semibold text-care-700">{{ number_format((float) $currentTotals['cash_in_amount'], 2) }}</p></div>
                    <div class="metric-tile"><p class="metric-label">Cash Out</p><p class="mt-2 text-lg font-semibold text-amber-700">{{ number_format((float) $currentTotals['cash_out_amount'], 2) }}</p></div>
                    <div class="metric-tile border-t-4 border-t-pharma-600"><p class="metric-label">Expected Cash</p><p class="mt-2 text-lg font-semibold text-pharma-700">{{ number_format((float) $currentTotals['expected_closing_cash'], 2) }}</p></div>
                </div>

                @if ($canManage)
                    <div class="grid gap-5 p-5 xl:grid-cols-2">
                        <form wire:submit="addEntry" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                            <div>
                                <p class="section-kicker">Manual Movement</p>
                                <h3 class="mt-1 text-lg font-semibold text-ink-950">Record Cash In or Cash Out</h3>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="block"><span class="field-label">Movement</span><select wire:model="entryType" class="field-control mt-1"><option value="cash_in">Cash In</option><option value="cash_out">Cash Out</option></select>@error('entryType') <span class="field-error">{{ $message }}</span> @enderror</label>
                                <label class="block"><span class="field-label">Amount</span><input wire:model="entryAmount" type="text" inputmode="decimal" class="field-control mt-1" placeholder="0.00">@error('entryAmount') <span class="field-error">{{ $message }}</span> @enderror</label>
                            </div>
                            <label class="block"><span class="field-label">Reason</span><input wire:model="entryReason" type="text" class="field-control mt-1" placeholder="Petty cash, supplier change, safe transfer">@error('entryReason') <span class="field-error">{{ $message }}</span> @enderror</label>
                            <button type="submit" class="btn-secondary">Record Movement</button>
                        </form>

                        <form wire:submit="closeShift" class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/40 p-4">
                            <div>
                                <p class="section-kicker text-amber-700">End Shift</p>
                                <h3 class="mt-1 text-lg font-semibold text-ink-950">Count and Close Drawer</h3>
                            </div>
                            <label class="block"><span class="field-label">Counted Closing Cash</span><input wire:model="countedClosingCash" type="text" inputmode="decimal" class="field-control mt-1" placeholder="{{ $currentTotals['expected_closing_cash'] }}">@error('countedClosingCash') <span class="field-error">{{ $message }}</span> @enderror</label>
                            <label class="block"><span class="field-label">Closing Notes</span><textarea wire:model="closingNotes" rows="3" class="field-control mt-1" placeholder="Explain any cash difference or handover note"></textarea>@error('closingNotes') <span class="field-error">{{ $message }}</span> @enderror</label>
                            <button type="submit" onclick="return confirm('Close this cash drawer shift?')" class="btn-primary">Close and Reconcile</button>
                        </form>
                    </div>
                @endif
            </section>
        @elseif ($canManage)
            <section class="surface-panel border-amber-200 bg-amber-50/50 p-5">
                <p class="section-kicker text-amber-700">No Active Shift</p>
                <h2 class="mt-1 text-xl font-semibold text-ink-950">Open the drawer before starting cash billing</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">Cash sales and cash refunds are attached to the active shift automatically. Non-cash payments do not change the drawer total.</p>
                <form wire:submit="openShift" class="mt-5 grid gap-4 lg:grid-cols-[220px_1fr_auto] lg:items-end">
                    <label class="block"><span class="field-label">Opening Float</span><input wire:model="openingFloat" type="text" inputmode="decimal" class="field-control mt-1" placeholder="0.00">@error('openingFloat') <span class="field-error">{{ $message }}</span> @enderror</label>
                    <label class="block"><span class="field-label">Opening Notes</span><input wire:model="openingNotes" type="text" class="field-control mt-1" placeholder="Handover or float note">@error('openingNotes') <span class="field-error">{{ $message }}</span> @enderror</label>
                    <button type="submit" class="btn-primary">Open Cash Drawer</button>
                </form>
            </section>
        @endif

        <section class="surface-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-200/80 bg-white p-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="inline-flex w-fit flex-wrap gap-1 rounded-lg bg-slate-100 p-1">
                    @foreach (['all' => 'All Shifts', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                        <button type="button" wire:click="$set('statusFilter', '{{ $value }}')" class="rounded-md px-3 py-2 text-sm font-semibold transition {{ $statusFilter === $value ? 'bg-white text-medical-800 shadow-sm' : 'text-slate-600 hover:bg-white/70 hover:text-ink-900' }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search shift or operator" class="field-control xl:max-w-md">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header"><tr><th class="px-4 py-3">Shift</th><th class="px-4 py-3">Opened</th><th class="px-4 py-3">Closed</th><th class="px-4 py-3">Expected</th><th class="px-4 py-3">Counted</th><th class="px-4 py-3">Variance</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($shifts as $shift)
                            <tr class="transition hover:bg-care-50/60">
                                <td class="px-4 py-3 align-top"><p class="font-semibold text-ink-950">{{ $shift->shift_number }}</p><p class="mt-1 text-xs text-slate-500">{{ $shift->openedBy?->name }}</p></td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $shift->opened_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $shift->closed_at?->format('d M Y, h:i A') ?: 'Still open' }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $shift->expected_closing_cash !== null ? number_format((float) $shift->expected_closing_cash, 2) : 'Pending' }}</td>
                                <td class="px-4 py-3 align-top text-slate-700">{{ $shift->counted_closing_cash !== null ? number_format((float) $shift->counted_closing_cash, 2) : 'Pending' }}</td>
                                <td class="px-4 py-3 align-top font-semibold {{ (float) $shift->variance_amount === 0.0 ? 'text-ink-900' : 'text-amber-700' }}">{{ $shift->variance_amount !== null ? number_format((float) $shift->variance_amount, 2) : 'Pending' }}</td>
                                <td class="px-4 py-3 align-top"><span class="badge {{ $shift->isOpen() ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">{{ ucfirst($shift->status) }}</span></td>
                                <td class="px-4 py-3 text-right align-top"><a href="{{ route('cash-drawer.show', $shift) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No cash drawer shifts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-shell>
