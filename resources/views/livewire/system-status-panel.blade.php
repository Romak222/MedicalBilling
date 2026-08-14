<section class="surface-panel overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 px-5 py-4">
        <div>
            <h2 class="text-lg font-semibold tracking-normal text-ink-950">System Status</h2>
            <p class="text-sm text-slate-600">Version {{ $status['application']['version'] ?? 'unknown' }}</p>
        </div>
        <button
            type="button"
            wire:click="refreshStatus"
            class="btn-primary"
        >
            Refresh
        </button>
    </div>

    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="surface-subtle p-4">
            <p class="metric-label">Database</p>
            <p class="mt-2 text-xl font-semibold {{ ($status['database']['connected'] ?? false) ? 'text-medical-700' : 'text-red-700' }}">
                {{ ($status['database']['connected'] ?? false) ? 'Connected' : 'Unavailable' }}
            </p>
            <p class="mt-1 break-words text-sm text-slate-600">{{ $status['database']['connection'] ?? 'unknown' }} / {{ $status['database']['driver'] ?? 'unknown' }}</p>
        </div>

        <div class="surface-subtle p-4">
            <p class="metric-label">Offline Status</p>
            <p class="mt-2 text-xl font-semibold text-medical-700">{{ $status['offline']['label'] ?? 'Unknown' }}</p>
            <p class="mt-1 text-sm text-slate-600">Local billing path remains independent of online services.</p>
        </div>

        <div class="surface-subtle p-4">
            <p class="metric-label">Runtime</p>
            <p class="mt-2 text-xl font-semibold text-ink-950">PHP {{ $status['runtime']['php'] ?? PHP_VERSION }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $status['runtime']['os'] ?? PHP_OS_FAMILY }}</p>
        </div>

        <div class="surface-subtle p-4">
            <p class="metric-label">Mode</p>
            <p class="mt-2 text-xl font-semibold text-ink-950">{{ $status['application']['deployment_mode'] ?? 'single-computer' }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $status['application']['environment'] ?? app()->environment() }}</p>
        </div>
    </div>

    <div class="grid gap-4 border-t border-slate-200/80 bg-slate-50/70 p-5 lg:grid-cols-2">
        <div>
            <p class="metric-label">Application Data Path</p>
            <p class="mt-2 break-all rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm">{{ $status['paths']['application_data'] ?? 'unknown' }}</p>
        </div>
        <div>
            <p class="metric-label">Backup Path</p>
            <p class="mt-2 break-all rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm">{{ $status['paths']['backup'] ?? 'unknown' }}</p>
        </div>
    </div>
</section>
