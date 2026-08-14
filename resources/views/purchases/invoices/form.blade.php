<x-layouts.app :title="config('app.name').' Purchase Invoice'">
    @isset($purchaseInvoice)
        <livewire:purchase-invoice-form :record="$purchaseInvoice" />
    @else
        <livewire:purchase-invoice-form />
    @endisset
</x-layouts.app>
