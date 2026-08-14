<x-layouts.app :title="config('app.name').' Catalogue Masters'">
    <x-app-shell page-title="Product Options" section-label="Catalogue Masters">
        <x-slot:actions>
            <a href="{{ route('products.index') }}" class="btn-secondary">Back to Products</a>
            <a href="{{ route('products.create') }}" class="btn-primary">Add New Product</a>
        </x-slot>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($options as $key => $option)
                <a href="{{ route('catalogue.options.show', $key) }}" class="surface-panel block p-5 transition hover:-translate-y-0.5 hover:border-medical-100 hover:shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="section-kicker">Option Detail</p>
                            <h2 class="mt-2 text-lg font-semibold text-ink-950">{{ $option['title'] }}</h2>
                            <p class="mt-2 text-sm text-slate-600">{{ $option['description'] }}</p>
                        </div>
                        <span class="badge bg-care-50 text-care-700">CRUD</span>
                    </div>
                </a>
            @endforeach
        </div>
    </x-app-shell>
</x-layouts.app>
