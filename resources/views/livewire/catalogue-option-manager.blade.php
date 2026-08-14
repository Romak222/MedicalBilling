@php
    use App\Support\CatalogueOptionRegistry;
@endphp

<x-app-shell :page-title="$config['title']" section-label="Product Options">
    <x-slot:actions>
        @if (session('status'))
            <span class="badge bg-medical-50 text-medical-700">{{ session('status') }}</span>
        @endif

        <a href="{{ route('catalogue.masters') }}" class="btn-secondary">All Options</a>
        <a href="{{ route('products.index') }}" class="btn-secondary">Back to Products</a>
        <a href="{{ route('catalogue.options.create', $type) }}" class="btn-primary">Add {{ $config['singular'] }}</a>
    </x-slot>

    <div class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <p class="section-kicker">Detail Page</p>
                    <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $config['title'] }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $config['description'] }}</p>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:min-w-96">
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search {{ strtolower($config['title']) }}" class="field-control">
                    <select wire:model.live="statusFilter" class="field-control">
                        <option value="active">Active</option>
                        <option value="all">All</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        @if ($showForm)
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                    <p class="section-kicker">{{ $recordId ? 'Edit' : 'Create' }}</p>
                    <h2 class="text-lg font-semibold text-ink-950">{{ $recordId ? 'Edit '.$config['singular'] : 'Add '.$config['singular'] }}</h2>
                </div>

                <form wire:submit="save" class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($config['fields'] as $field)
                        @php
                            $span = $field['type'] === 'textarea' ? 'lg:col-span-2' : '';
                        @endphp

                        <label class="block {{ $span }}">
                            <span class="text-sm font-medium text-ink-700">{{ $field['label'] }}</span>

                            @if ($field['type'] === 'textarea')
                                <textarea wire:model="form.{{ $field['key'] }}" rows="3" class="field-control mt-1"></textarea>
                            @elseif ($field['type'] === 'category_parent')
                                <select wire:model="form.{{ $field['key'] }}" class="field-control mt-1">
                                    <option value="">No parent</option>
                                    @foreach ($parentCategories as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input
                                    wire:model="form.{{ $field['key'] }}"
                                    type="{{ $field['type'] === 'date' ? 'date' : 'text' }}"
                                    inputmode="{{ $field['type'] === 'decimal' ? 'decimal' : 'text' }}"
                                    class="field-control mt-1 {{ ($field['uppercase'] ?? false) ? 'uppercase' : '' }}"
                                >
                            @endif

                            @error('form.'.$field['key'])
                                <span class="mt-1 block text-xs text-red-700">{{ $message }}</span>
                            @enderror
                        </label>
                    @endforeach

                    <div class="flex justify-end gap-3 lg:col-span-2">
                        <a href="{{ route('catalogue.options.show', $type) }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save {{ $config['singular'] }}</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="surface-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="table-header">
                        <tr>
                            @foreach ($config['columns'] as $column)
                                <th class="px-4 py-3">{{ $column['label'] }}</th>
                            @endforeach
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($records as $item)
                            <tr class="transition hover:bg-care-50/60 {{ $item->is_active ? 'bg-white' : 'bg-slate-50/70' }}">
                                @foreach ($config['columns'] as $column)
                                    <td class="min-w-44 px-4 py-3 align-top text-slate-700">
                                        {{ CatalogueOptionRegistry::displayValue($item, $column) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 align-top">
                                    <span class="badge {{ $item->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="min-w-48 px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('catalogue.options.view', ['type' => $type, 'record' => $item]) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            View
                                        </a>
                                        <a href="{{ route('catalogue.options.edit', ['type' => $type, 'record' => $item]) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                            Edit
                                        </a>

                                        @if ($item->is_active)
                                            <button type="button" wire:click="deactivate({{ $item->id }})" class="rounded-md border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                                                Delete
                                            </button>
                                        @else
                                            <button type="button" wire:click="restore({{ $item->id }})" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-ink-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50">
                                                Restore
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($config['columns']) + 2 }}" class="px-4 py-12 text-center text-sm font-medium text-slate-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($records->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $records->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-shell>
