@php
    use App\Support\CatalogueOptionRegistry;
@endphp

<x-layouts.app :title="config('app.name').' '.$config['singular']">
    <x-app-shell :page-title="$config['singular'].' Detail'" section-label="Product Options">
        <x-slot:actions>
            <a href="{{ route('catalogue.options.show', $type) }}" class="btn-secondary">Back to {{ $config['title'] }}</a>
            <a href="{{ route('catalogue.options.edit', ['type' => $type, 'record' => $record]) }}" class="btn-primary">Edit {{ $config['singular'] }}</a>
        </x-slot>

        <div class="space-y-5">
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Read Only</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $record->name ?? $config['singular'] }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $config['description'] }}</p>
                    </div>
                    <span class="badge {{ $record->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $record->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($config['fields'] as $field)
                        <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                            <dt class="text-xs font-semibold uppercase text-slate-500">{{ $field['label'] }}</dt>
                            <dd class="mt-2 text-sm font-semibold text-ink-900">{{ CatalogueOptionRegistry::displayFieldValue($record, $field) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
