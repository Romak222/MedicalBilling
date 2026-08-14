<x-layouts.app :title="config('app.name').' Product'">
    @isset($product)
        <livewire:product-form :record="$product" />
    @else
        <livewire:product-form />
    @endisset
</x-layouts.app>
