<x-layouts.app :title="config('app.name').' Purchase Order'">
    @isset($purchaseOrder)
        <livewire:purchase-order-form :record="$purchaseOrder" />
    @else
        <livewire:purchase-order-form />
    @endisset
</x-layouts.app>
