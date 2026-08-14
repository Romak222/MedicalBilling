<x-layouts.app :title="config('app.name').' Status'">
    @php
        $setupComplete = $setupComplete ?? false;
        $primaryStore = $primaryStore ?? null;
    @endphp

    <x-app-shell :page-title="$primaryStore?->name ?? config('app.name')" section-label="Phase 5">
        <section class="space-y-5">
            <div class="surface-panel overflow-hidden">
                <div class="grid gap-0 lg:grid-cols-[1.5fr_1fr]">
                    <div class="border-b border-slate-200/80 p-5 lg:border-b-0 lg:border-r">
                        <p class="section-kicker">Store Foundation</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-normal text-ink-950">Local pharmacy workspace</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Access control, setup profile, catalogue foundation, and audit logging are active for this phase.</p>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-200/80">
                        <div class="p-5">
                            <p class="metric-label">Phase</p>
                            <p class="mt-2 text-2xl font-semibold text-care-700">5</p>
                        </div>
                        <div class="p-5">
                            <p class="metric-label">Mode</p>
                            <p class="mt-2 text-2xl font-semibold text-medical-700">Local</p>
                        </div>
                        <div class="p-5">
                            <p class="metric-label">Setup</p>
                            <p class="mt-2 text-2xl font-semibold {{ $setupComplete ? 'text-medical-700' : 'text-alert-700' }}">{{ $setupComplete ? 'Done' : 'Open' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <livewire:system-status-panel />

            <div class="grid gap-4 xl:grid-cols-3">
                <section class="metric-tile">
                    <h2 class="metric-label">Foundation</h2>
                    <p class="mt-3 text-3xl font-semibold tracking-normal text-ink-950">Laravel 12</p>
                    <p class="mt-2 text-sm text-slate-600">Blade, Livewire, Alpine and Tailwind are installed through local packages.</p>
                </section>

                <section class="metric-tile">
                    <h2 class="metric-label">Storage</h2>
                    <p class="mt-3 text-3xl font-semibold tracking-normal text-ink-950">SQLite</p>
                    <p class="mt-2 text-sm text-slate-600">Local development uses SQLite; LAN mode is planned for MySQL or MariaDB.</p>
                </section>

                <section class="metric-tile">
                    <h2 class="metric-label">Setup</h2>
                    <p class="mt-3 text-3xl font-semibold tracking-normal {{ $setupComplete ? 'text-medical-700' : 'text-alert-700' }}">{{ $setupComplete ? 'Complete' : 'Pending' }}</p>
                    <p class="mt-2 text-sm text-slate-600">Store profile, pharmacist record, owner account and local operating paths.</p>
                </section>
            </div>
        </section>
    </x-app-shell>
</x-layouts.app>
