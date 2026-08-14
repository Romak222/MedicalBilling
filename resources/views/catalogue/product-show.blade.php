<x-layouts.app :title="config('app.name').' Product Detail'">
    <x-app-shell :page-title="$product->name" section-label="Product Detail">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" class="btn-secondary">Back to Products</a>
            <a href="{{ route('products.edit', $product) }}" class="btn-primary">Edit Product</a>
        </x-slot>

        <div class="space-y-5">
            <section class="surface-panel p-5">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="section-kicker">Read Only</p>
                        <h2 class="mt-1 text-xl font-semibold text-ink-950">{{ $product->name }}</h2>
                        <p class="mt-2 text-sm text-slate-600">{{ $product->generic_name ?: 'No generic name' }}</p>
                    </div>
                    <span class="badge {{ $product->is_active ? 'bg-medical-50 text-medical-700' : 'bg-slate-200 text-slate-600' }}">
                        {{ $product->is_active ? 'Active' : 'Deleted' }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">SKU</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->sku ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Manufacturer</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->manufacturer?->name ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Category</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->category?->name ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Type / Form</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->product_type ?: 'Not set' }} / {{ $product->form ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Strength / Pack</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->strength ?: 'Not set' }} / {{ $product->pack_size ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Tax / HSN</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->taxRate?->name ?: 'Not set' }} / {{ $product->hsn_code ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Base Unit</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->baseUnit?->unit_name ?: 'Not set' }} {{ $product->baseUnit?->unit_code ? '/ '.$product->baseUnit->unit_code : '' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Barcode</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->barcodes->first()?->barcode ?: 'Not set' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Flags</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">
                            {{ $product->prescription_required ? 'Prescription required' : 'No prescription flag' }}
                            / {{ $product->controlled_medicine ? 'Controlled' : 'Not controlled' }}
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-4 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase text-slate-500">Composition</dt>
                        <dd class="mt-2 text-sm font-semibold text-ink-900">{{ $product->composition ?: 'Not set' }}</dd>
                    </div>
                </dl>
            </section>
        </div>
    </x-app-shell>
</x-layouts.app>
