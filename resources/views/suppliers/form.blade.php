<x-layouts.app :title="config('app.name').' Supplier'">
    @isset($supplier)
        <livewire:supplier-form :record="$supplier" />
    @else
        <livewire:supplier-form />
    @endisset
</x-layouts.app>
